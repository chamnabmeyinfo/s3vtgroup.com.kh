<?php
/**
 * XML Sitemap Index Generator
 * Serves as sitemap_index.xml for Google Search Console and other crawlers.
 */
ob_start();
require_once __DIR__ . '/bootstrap/app.php';
ob_end_clean();

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

// Get base URL (same logic as sitemap.php)
$baseUrl = config('app.url');
if (empty($baseUrl)) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
    $basePath = dirname($scriptPath);
    $basePath = rtrim($basePath, '/');
    if ($basePath === '' || $basePath === '.' || $basePath === '/') {
        $basePath = '';
    }
    $baseUrl = $protocol . $host . $basePath;
}
$baseUrl = rtrim($baseUrl, '/\\');

// Main sitemap URL (use .xml extension for better crawler compatibility)
$sitemapUrl = $baseUrl . '/sitemap.xml';

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
echo '<sitemap>';
echo '<loc>' . escape($sitemapUrl) . '</loc>';
echo '<lastmod>' . date('Y-m-d') . '</lastmod>';
echo '</sitemap>';
echo '</sitemapindex>';
