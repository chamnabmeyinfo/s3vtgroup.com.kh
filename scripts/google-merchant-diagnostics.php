<?php
/**
 * Compare Content API products.list with what you see in Merchant Center UI.
 *
 * Usage: php scripts/google-merchant-diagnostics.php
 *
 * If http_code is 403/400 mentioning "multi-client", set GOOGLE_MERCHANT_MERCHANT_ID
 * to a sub-account (non-MCA) ID — Content API cannot use the parent MCA ID for products.
 */
require_once __DIR__ . '/../bootstrap/app.php';

if (!App\Services\GoogleMerchantProductSync::isEnabled()) {
    fwrite(STDERR, "Google Merchant sync is disabled or not configured.\n");
    exit(1);
}

$cfg = config('google_merchant', []) ?: [];
echo "Merchant ID in config: " . ($cfg['merchant_id'] ?? '') . "\n";
echo "Credentials: " . ($cfg['credentials_path'] ?? '') . " (readable: " . (is_readable($cfg['credentials_path'] ?? '') ? 'yes' : 'no') . ")\n\n";

$sync = new App\Services\GoogleMerchantProductSync();
$result = $sync->listProductsFromApi(250);

echo "HTTP code: " . $result['http_code'] . "\n";
echo "Products returned by API: " . $result['count'] . "\n";

if ($result['error_body'] !== '') {
    echo "\n--- Error body (first 2000 chars) ---\n";
    echo substr($result['error_body'], 0, 2000) . "\n";
}

if (!empty($result['product_ids'])) {
    echo "\nSample product IDs (up to 20):\n";
    foreach ($result['product_ids'] as $id) {
        echo "  - " . $id . "\n";
    }
}

if ($result['http_code'] >= 200 && $result['http_code'] < 300 && $result['count'] > 0) {
    echo "\nThe API sees products for this Merchant ID. If Merchant Center UI shows 0, use the SAME account ID in the UI (account switcher) or clear filters.\n";
} elseif ($result['http_code'] >= 200 && $result['http_code'] < 300 && $result['count'] === 0) {
    echo "\nThe API returned 0 products for this Merchant ID — inserts may be going to a different account, or MCA parent ID is used (see Google: merchantId cannot be multi-client account).\n";
}

exit($result['http_code'] >= 200 && $result['http_code'] < 300 ? 0 : 2);
