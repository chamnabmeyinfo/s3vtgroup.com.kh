<?php
/**
 * Full resync of active products to Google Merchant Center (CLI).
 * Usage: php scripts/google-merchant-resync.php
 */
require_once __DIR__ . '/../bootstrap/app.php';

if (!App\Services\GoogleMerchantProductSync::isEnabled()) {
    fwrite(STDERR, "Google Merchant sync is disabled or not configured.\n");
    exit(1);
}

$rows = db()->fetchAll('SELECT id FROM products WHERE is_active = 1 ORDER BY id');
$sync = new App\Services\GoogleMerchantProductSync();
$ok = 0;
$fail = 0;
foreach ($rows as $r) {
    $id = (int) $r['id'];
    try {
        $sync->syncProduct($id);
        $ok++;
        echo "OK id={$id}\n";
    } catch (Throwable $e) {
        $fail++;
        echo "FAIL id={$id}: {$e->getMessage()}\n";
    }
}
echo "Done. Success: {$ok}, Failed: {$fail}\n";
exit($fail > 0 ? 2 : 0);

