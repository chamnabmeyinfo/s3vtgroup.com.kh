<?php
/**
 * Compare Content API products.list with what you see in Merchant Center UI.
 *
 * Uses a minimal bootstrap (vendor + config only) so CLI runs do not load sessions,
 * hooks, or the web error handler — those can cause silent exit 255 on some servers.
 *
 * Usage: php scripts/google-merchant-diagnostics.php
 *
 * If http_code is 403/400 mentioning "multi-client", set GOOGLE_MERCHANT_MERCHANT_ID
 * to a sub-account (non-MCA) ID — Content API cannot use the parent MCA ID for products.
 */
if (PHP_SAPI === 'cli') {
    ini_set('display_errors', '1');
}
error_reporting(E_ALL);

$projectRoot = dirname(__DIR__);

register_shutdown_function(static function (): void {
    $err = error_get_last();
    if ($err === null) {
        return;
    }
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (!in_array($err['type'], $fatalTypes, true)) {
        return;
    }
    fwrite(STDERR, sprintf(
        "Fatal: %s in %s on line %d\n",
        $err['message'],
        $err['file'],
        $err['line']
    ));
});

if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    fwrite(STDERR, 'PHP 8.1+ is required (google/auth). This binary reports: ' . PHP_VERSION . "\n");
    exit(1);
}

$vendorAutoload = $projectRoot . '/vendor/autoload.php';
if (!is_file($vendorAutoload)) {
    fwrite(STDERR, "Composer dependencies are missing (no vendor/autoload.php).\nFrom {$projectRoot} run: composer install\n");
    exit(1);
}
require_once $vendorAutoload;

// Intentionally skip vlucas/phpdotenv here: it throws on any invalid .env line (e.g. stray text
// without KEY=value). config/google_merchant.php reads .env with tolerant parsing instead.
$cfg = require $projectRoot . '/config/google_merchant.php';
if (!is_array($cfg)) {
    fwrite(STDERR, "Invalid config/google_merchant.php\n");
    exit(1);
}

$syncFile = $projectRoot . '/app/Services/GoogleMerchantProductSync.php';
if (!is_file($syncFile)) {
    fwrite(STDERR, "Missing app/Services/GoogleMerchantProductSync.php\n");
    exit(1);
}
require_once $syncFile;

if (!\App\Services\GoogleMerchantProductSync::isConfigEnabled($cfg)) {
    fwrite(STDERR, "Google Merchant sync is disabled or not configured.\n");
    exit(1);
}

if (!class_exists(\Google\Auth\Credentials\ServiceAccountCredentials::class)) {
    fwrite(STDERR, "Package google/auth is missing. From {$projectRoot} run: composer install\n");
    exit(1);
}

echo 'PHP ' . PHP_VERSION . "\n";
echo 'Merchant ID in config: ' . ($cfg['merchant_id'] ?? '') . "\n";
echo 'Credentials: ' . ($cfg['credentials_path'] ?? '') . ' (readable: ' . (is_readable($cfg['credentials_path'] ?? '') ? 'yes' : 'no') . ")\n\n";

try {
    $sync = new \App\Services\GoogleMerchantProductSync($cfg);
    $result = $sync->listProductsFromApi(250);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Diagnostics failed before HTTP response: ' . get_class($e) . ': ' . $e->getMessage() . "\n");
    fwrite(STDERR, "Common causes: outbound HTTPS to googleapis.com; valid service account JSON; Merchant Center linked the service account.\n");
    $prev = $e->getPrevious();
    if ($prev instanceof \Throwable) {
        fwrite(STDERR, 'Caused by: ' . get_class($prev) . ': ' . $prev->getMessage() . "\n");
    }
    exit(3);
}

echo 'HTTP code: ' . $result['http_code'] . "\n";
echo 'Products returned by API: ' . $result['count'] . "\n";

if ($result['error_body'] !== '') {
    echo "\n--- Error body (first 2000 chars) ---\n";
    echo substr($result['error_body'], 0, 2000) . "\n";
}

if ($result['http_code'] === 401 && str_contains($result['error_body'], 'account_access_denied')) {
    echo "\n--- Fix (401 account_access_denied) ---\n";
    echo "The service account in your JSON key is not allowed to use Merchant ID " . ($cfg['merchant_id'] ?? '') . ".\n";
    echo "1) Open the credentials JSON and copy \"client_email\" (…@….iam.gserviceaccount.com).\n";
    echo "2) Merchant Center → gear → Account settings → Users (or People and access): invite that email with Admin or Standard.\n";
    echo "3) Merchant Center → Tools → Content API (or Business settings → Google Cloud): link the same Cloud project that owns this service account.\n";
    echo "4) If this ID is a multi-client parent, use the child merchant ID in GOOGLE_MERCHANT_MERCHANT_ID and grant access there too.\n";
}

if (!empty($result['product_ids'])) {
    echo "\nSample product IDs (up to 20):\n";
    foreach ($result['product_ids'] as $id) {
        echo '  - ' . $id . "\n";
    }
}

if ($result['http_code'] >= 200 && $result['http_code'] < 300 && $result['count'] > 0) {
    echo "\nThe API sees products for this Merchant ID. If Merchant Center UI shows 0, use the SAME account ID in the UI (account switcher) or clear filters.\n";
} elseif ($result['http_code'] >= 200 && $result['http_code'] < 300 && $result['count'] === 0) {
    echo "\nThe API returned 0 products for this Merchant ID — inserts may be going to a different account, or MCA parent ID is used (see Google: merchantId cannot be multi-client account).\n";
}

exit($result['http_code'] >= 200 && $result['http_code'] < 300 ? 0 : 2);
