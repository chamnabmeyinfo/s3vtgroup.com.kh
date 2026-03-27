<?php

namespace App\Helpers;

use App\Models\Product;

/**
 * Tab-delimited primary feed rows for Google Merchant Center (scheduled fetch fallback).
 * Field names align with Google's text feed spec; mirrors rules in GoogleMerchantProductSync.
 */
class GoogleMerchantFeedHelper
{
    /**
     * @return array<int, array<string, string>>
     */
    public static function buildRows(): array
    {
        $cfg = config('google_merchant', []) ?: [];
        $currency = (string) ($cfg['currency'] ?? 'USD');
        $brand = (string) ($cfg['default_brand'] ?? '');

        $model = new Product();
        $products = $model->getAll(['limit' => 100000, 'offset' => 0]);

        $rows = [];
        foreach ($products as $p) {
            if (empty($p['is_active'])) {
                continue;
            }

            $variants = [];
            try {
                $variants = db()->fetchAll(
                    'SELECT * FROM product_variants WHERE product_id = :pid AND is_active = 1 ORDER BY sort_order, id',
                    ['pid' => (int) $p['id']]
                );
            } catch (\Throwable $e) {
                $variants = [];
            }

            if (count($variants) > 0) {
                $groupId = self::sanitizedOfferId('g' . $p['id']);
                foreach ($variants as $v) {
                    if (!self::variantRowHasPrice($v, $p)) {
                        continue;
                    }
                    $row = self::rowFromVariant($p, $v, $groupId, $currency, $brand);
                    if ($row !== null) {
                        $rows[] = $row;
                    }
                }
            } else {
                $row = self::rowFromParent($p, $currency, $brand);
                if ($row !== null) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    /**
     * @param array<string, string> $row
     */
    public static function escapeTsvField(string $value): string
    {
        $value = str_replace(["\r\n", "\r", "\n"], ' ', $value);
        $value = str_replace("\t", ' ', $value);
        return $value;
    }

    /**
     * @return array<string, string>|null
     */
    private static function rowFromParent(array $p, string $currency, string $brand): ?array
    {
        $price = $p['sale_price'] !== null && $p['sale_price'] !== ''
            ? (float) $p['sale_price']
            : (float) ($p['price'] ?? 0);
        if ($price <= 0) {
            return null;
        }
        $id = !empty(trim((string) ($p['sku'] ?? '')))
            ? self::sanitizedOfferId(trim((string) $p['sku']))
            : self::sanitizedOfferId('p' . $p['id']);

        if (empty($p['image'])) {
            return null;
        }
        $image = image_url($p['image']);
        if ($image === '') {
            return null;
        }

        $desc = strip_tags((string) ($p['short_description'] ?? $p['description'] ?? ''));
        $desc = trim(preg_replace('/\s+/', ' ', $desc));
        if (strlen($desc) > 5000) {
            $desc = substr($desc, 0, 4997) . '...';
        }

        $link = url('product.php?slug=' . rawurlencode((string) $p['slug']));
        $availability = self::mapAvailability((string) ($p['stock_status'] ?? 'in_stock'));
        $priceStr = number_format($price, 2, '.', '') . ' ' . $currency;

        return [
            'id' => $id,
            'title' => mb_substr((string) $p['name'], 0, 150),
            'description' => $desc !== '' ? $desc : (string) $p['name'],
            'link' => $link,
            'image link' => $image,
            'availability' => $availability,
            'price' => $priceStr,
            'brand' => $brand,
            'condition' => 'new',
            'item group id' => '',
        ];
    }

    /**
     * @return array<string, string>|null
     */
    private static function rowFromVariant(array $product, array $variant, string $itemGroupId, string $currency, string $brand): ?array
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
        $id = $sku !== '' ? self::sanitizedOfferId($sku) : self::sanitizedOfferId('v' . $variant['id']);

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

        $desc = strip_tags((string) ($product['short_description'] ?? $product['description'] ?? ''));
        $desc = trim(preg_replace('/\s+/', ' ', $desc));
        if (strlen($desc) > 5000) {
            $desc = substr($desc, 0, 4997) . '...';
        }

        $link = url('product.php?slug=' . rawurlencode((string) $product['slug']));
        $availability = self::mapAvailability((string) ($variant['stock_status'] ?? $product['stock_status'] ?? 'in_stock'));
        $priceStr = number_format($price, 2, '.', '') . ' ' . $currency;

        return [
            'id' => $id,
            'title' => mb_substr($title, 0, 150),
            'description' => $desc !== '' ? $desc : $title,
            'link' => $link,
            'image link' => $image,
            'availability' => $availability,
            'price' => $priceStr,
            'brand' => $brand,
            'condition' => 'new',
            'item group id' => $itemGroupId,
        ];
    }

    private static function sanitizedOfferId(string $raw): string
    {
        $s = preg_replace('/[^a-zA-Z0-9_-]/', '', $raw);
        if ($s === '') {
            $s = 'x';
        }
        return substr($s, 0, 50);
    }

    private static function variantRowHasPrice(array $variant, array $productRow): bool
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

    private static function mapAvailability(string $status): string
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
}
