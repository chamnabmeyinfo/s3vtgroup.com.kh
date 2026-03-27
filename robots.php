<?php
/**
 * Dynamic robots.txt generator
 * References sitemap for search engine discovery.
 */
require_once __DIR__ . '/bootstrap/app.php';

header('Content-Type: text/plain; charset=utf-8');

$baseUrl = rtrim(config('app.url') ?: (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');

echo "User-agent: *\n";
echo "Allow: /\n";
echo "\n";
echo "Sitemap: {$baseUrl}/sitemap_index.xml\n";
echo "Sitemap: {$baseUrl}/sitemap.xml\n";
