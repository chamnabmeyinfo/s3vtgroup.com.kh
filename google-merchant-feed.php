<?php
/**
 * Optional tab-delimited product feed for Google Merchant Center (scheduled fetch).
 *
 * Enable in config: GOOGLE_MERCHANT_FEED_ENABLED=true and set GOOGLE_MERCHANT_FEED_TOKEN
 * to a long random secret. Then register this URL in Merchant Center with the token:
 *
 *   https://YOUR-DOMAIN/google-merchant-feed.php?token=YOUR_TOKEN
 *
 * This complements the Content API push (GoogleMerchantProductSync); use either or both.
 */
require_once __DIR__ . '/bootstrap/app.php';

use App\Helpers\GoogleMerchantFeedHelper;

$cfg = config('google_merchant', []) ?: [];

if (empty($cfg['feed_enabled'])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found.';
    exit;
}

$expected = (string) ($cfg['feed_token'] ?? '');
if ($expected === '') {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Feed token not configured.';
    exit;
}

$token = $_GET['token'] ?? '';
if (!hash_equals($expected, (string) $token)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden.';
    exit;
}

$headers = [
    'id',
    'title',
    'description',
    'link',
    'image link',
    'availability',
    'price',
    'brand',
    'condition',
    'item group id',
];

$rows = GoogleMerchantFeedHelper::buildRows();

header('Content-Type: text/tab-separated-values; charset=utf-8');
header('Content-Disposition: inline; filename="google-merchant-feed.txt"');

$out = fopen('php://output', 'w');
if ($out === false) {
    http_response_code(500);
    exit;
}

fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
fputcsv($out, $headers, "\t");

foreach ($rows as $r) {
    $line = [];
    foreach ($headers as $h) {
        $line[] = GoogleMerchantFeedHelper::escapeTsvField($r[$h] ?? '');
    }
    fputcsv($out, $line, "\t");
}

fclose($out);
