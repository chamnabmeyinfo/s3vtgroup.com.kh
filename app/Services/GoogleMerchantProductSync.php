<?php

namespace App\Services;

use App\Models\Product;
use Exception;
use Google\Auth\Credentials\ServiceAccountCredentials;

/**
 * Push active products to Google Merchant Center via Content API for Shopping.
 */
class GoogleMerchantProductSync
{
    private const CONTENT_SCOPE = 'https://www.googleapis.com/auth/content';
    private const API_BASE = 'https://shoppingcontent.googleapis.com/content/v2.1';

    /** @var array<string,mixed> */
    private $cfg;

    public function __construct()
    {
        $this->cfg = config('google_merchant', []) ?: [];
    }

    public static function isEnabled(): bool
    {
        $cfg = config('google_merchant', []) ?: [];
        if (empty($cfg['enabled'])) {
            return false;
        }
        $mid = trim((string) ($cfg['merchant_id'] ?? ''));
        $path = (string) ($cfg['credentials_path'] ?? '');
        if ($mid === '' || $path === '' || !is_readable($path)) {
            return false;
        }
        return true;
    }

    private function getAccessToken(): string
    {
        $path = $this->cfg['credentials_path'];
        $json = json_decode((string) file_get_contents($path), true);
        if (!is_array($json)) {
            throw new Exception('Invalid Google Merchant credentials JSON');
        }
        $creds = new ServiceAccountCredentials([self::CONTENT_SCOPE], $json);
        $token = $creds->fetchAuthToken();
        if (empty($token['access_token'])) {
            throw new Exception('Google Merchant: failed to obtain access token');
        }
        return $token['access_token'];
    }

    private function contentProductId(string $offerId): string
    {
        return $this->cfg['channel'] . ':' . $this->cfg['content_language'] . ':' . $this->cfg['target_country'] . ':' . $offerId;
    }

    public function deleteOfferByOfferId(string $offerId): void
    {
        if (!self::isEnabled() || $offerId === '') {
            return;
        }
        $merchantId = $this->cfg['merchant_id'];
        $pid = rawurlencode($this->contentProductId($offerId));
        $url = self::API_BASE . '/' . rawurlencode($merchantId) . '/products/' . $pid;
        $this->request('DELETE', $url, null);
    }

    /**
     * @param int[] $variantIds
     */
    public function deleteAllForProductBeforeDbDelete(int $productId, array $variantIds): void
    {
        if (!self::isEnabled()) {
            return;
        }
        foreach ($variantIds as $vid) {
            try {
                $this->deleteOfferByOfferId($this->variantOfferId((int) $vid));
            } catch (Exception $e) {
                error_log('Google Merchant delete variant offer: ' . $e->getMessage());
            }
        }
        try {
            $this->deleteOfferByOfferId($this->parentOfferId($productId));
        } catch (Exception $e) {
            error_log('Google Merchant delete parent offer: ' . $e->getMessage());
        }
    }

    /**
     * @param int[] $removedVariantIds
     */
    public function deleteOffersForRemovedVariants(array $removedVariantIds): void
    {
        foreach ($removedVariantIds as $vid) {
            try {
                $this->deleteOfferByOfferId($this->variantOfferId((int) $vid));
            } catch (Exception $e) {
                error_log('Google Merchant delete removed variant: ' . $e->getMessage());
            }
        }
    }

    public function syncProduct(int $productId): void
    {
        if (!self::isEnabled()) {
            return;
        }
        $model = new Product();
        $row = $model->getById($productId);
        if (!$row) {
            return;
        }

        if (empty($row['is_active'])) {
            $this->removeProductFromMerchant($productId);
            return;
        }

        $variants = [];
        try {
            $variants = db()->fetchAll(
                'SELECT * FROM product_variants WHERE product_id = :pid AND is_active = 1 ORDER BY sort_order, id',
                ['pid' => $productId]
            );
        } catch (Exception $e) {
            $variants = [];
        }

        if (count($variants) > 0) {
            $groupId = $this->sanitizedOfferId('g' . $productId);
            foreach ($variants as $v) {
                if (!$this->variantRowHasPrice($v, $row)) {
                    continue;
                }
                $body = $this->buildProductBodyFromVariant($row, $v, $groupId);
                if ($body === null) {
                    continue;
                }
                $this->insertProduct($body);
            }
            try {
                $this->deleteOfferByOfferId($this->parentOfferId($productId));
            } catch (Exception $e) {
            }
            return;
        }

        $body = $this->buildProductBodyFromParent($row);
        if ($body === null) {
            return;
        }
        $this->insertProduct($body);
    }

    private function removeProductFromMerchant(int $productId): void
    {
        $variantIds = [];
        try {
            $vs = db()->fetchAll(
                'SELECT id FROM product_variants WHERE product_id = :pid',
                ['pid' => $productId]
            );
            $variantIds = array_map('intval', array_column($vs, 'id'));
        } catch (Exception $e) {
        }
        $this->deleteAllForProductBeforeDbDelete($productId, $variantIds);
    }

    private function parentOfferId(int $productId): string
    {
        return $this->sanitizedOfferId('p' . $productId);
    }

    private function variantOfferId(int $variantId): string
    {
        return $this->sanitizedOfferId('v' . $variantId);
    }

    private function sanitizedOfferId(string $raw): string
    {
        $s = preg_replace('/[^a-zA-Z0-9_-]/', '', $raw);
        if ($s === '') {
            $s = 'x';
        }
        return substr($s, 0, 50);
    }

    private function variantRowHasPrice(array $variant, array $productRow): bool
    {
        $p = $variant['sale_price'] !== null && $variant['sale_price'] !== ''
            ? (float) $variant['sale_price']
            : (float) ($variant['price'] ?? 0);
        if ($p > 0) {
            return true;
        }
        $base = $productRow['sale_price'] !== null && $productRow['sale_price'] !== ''
            ? (float) $productRow['sale_price']
            : (float) ($productRow['price'] ?? 0);
        return $base > 0;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function buildProductBodyFromParent(array $row): ?array
    {
        $price = $row['sale_price'] !== null && $row['sale_price'] !== ''
            ? (float) $row['sale_price']
            : (float) ($row['price'] ?? 0);
        if ($price <= 0) {
            return null;
        }
        $offerId = !empty(trim((string) ($row['sku'] ?? '')))
            ? $this->sanitizedOfferId(trim((string) $row['sku']))
            : $this->parentOfferId((int) $row['id']);

        $image = !empty($row['image']) ? image_url($row['image']) : '';
        if ($image === '') {
            return null;
        }

        return $this->assembleBody($row, $offerId, $price, $image, null, (string) ($row['stock_status'] ?? 'in_stock'));
    }

    /**
     * @return array<string,mixed>|null
     */
    private function buildProductBodyFromVariant(array $product, array $variant, string $itemGroupId): ?array
    {
        $price = $variant['sale_price'] !== null && $variant['sale_price'] !== ''
            ? (float) $variant['sale_price']
            : (float) ($variant['price'] ?? 0);
        if ($price <= 0) {
            $price = $product['sale_price'] !== null && $product['sale_price'] !== ''
                ? (float) $product['sale_price']
                : (float) ($product['price'] ?? 0);
        }
        if ($price <= 0) {
            return null;
        }

        $sku = trim((string) ($variant['sku'] ?? ''));
        $offerId = $sku !== '' ? $this->sanitizedOfferId($sku) : $this->variantOfferId((int) $variant['id']);

        $img = trim((string) ($variant['image'] ?? ''));
        if ($img === '') {
            $img = trim((string) ($product['image'] ?? ''));
        }
        if ($img === '') {
            return null;
        }
        $image = image_url($img);

        $title = trim((string) ($variant['name'] ?? ''));
        if ($title === '') {
            $title = (string) $product['name'];
        }

        $virtual = $product;
        $virtual['name'] = $title;
        $stock = (string) ($variant['stock_status'] ?? $product['stock_status'] ?? 'in_stock');

        return $this->assembleBody($virtual, $offerId, $price, $image, $itemGroupId, $stock);
    }

    /**
     * @return array<string,mixed>
     */
    private function assembleBody(array $row, string $offerId, float $price, string $imageLink, ?string $itemGroupId, string $stockStatus): array
    {
        $currency = (string) $this->cfg['currency'];
        $desc = strip_tags((string) ($row['short_description'] ?? $row['description'] ?? ''));
        $desc = trim(preg_replace('/\s+/', ' ', $desc));
        if (strlen($desc) > 5000) {
            $desc = substr($desc, 0, 4997) . '...';
        }

        $link = url('product.php?slug=' . rawurlencode((string) $row['slug']));

        $availability = $this->mapAvailability($stockStatus);

        $body = [
            'offerId' => $offerId,
            'title' => mb_substr((string) $row['name'], 0, 150),
            'description' => $desc !== '' ? $desc : (string) $row['name'],
            'link' => $link,
            'imageLink' => $imageLink,
            'contentLanguage' => (string) $this->cfg['content_language'],
            'targetCountry' => (string) $this->cfg['target_country'],
            'channel' => (string) $this->cfg['channel'],
            'availability' => $availability,
            'condition' => 'new',
            'brand' => (string) $this->cfg['default_brand'],
            'price' => [
                'value' => number_format($price, 2, '.', ''),
                'currency' => $currency,
            ],
        ];
        if ($itemGroupId !== null) {
            $body['itemGroupId'] = $itemGroupId;
        }
        return $body;
    }

    private function mapAvailability(string $status): string
    {
        switch ($status) {
            case 'out_of_stock':
                return 'out of stock';
            case 'on_order':
                return 'backorder';
            default:
                return 'in stock';
        }
    }

    /**
     * @param array<string,mixed> $body
     */
    private function insertProduct(array $body): void
    {
        $merchantId = $this->cfg['merchant_id'];
        $url = self::API_BASE . '/' . rawurlencode($merchantId) . '/products';
        $this->request('POST', $url, $body);
    }

    private function request(string $method, string $url, ?array $body): void
    {
        $token = $this->getAccessToken();
        $ch = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 200 && $code < 300) {
            return;
        }
        if ($method === 'DELETE' && $code === 404) {
            return;
        }
        $msg = is_string($resp) ? $resp : '';
        throw new Exception('Google Merchant API HTTP ' . $code . ': ' . $msg);
    }
}

