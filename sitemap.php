<?php
/**
 * XML Sitemap Generator
 * Includes: homepage, static pages, products, categories, services, CMS pages.
 */
ob_start();
require_once __DIR__ . '/bootstrap/app.php';
ob_end_clean();

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

try {
use App\Models\Product;
use App\Models\Category;
use App\Models\Service;
use App\Models\Page;

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
$baseUrl = rtrim($baseUrl, '/');

$productModel = new Product();
$categoryModel = new Category();
$serviceModel = new Service();
$pageModel = new Page();

$products = $productModel->getAll(['limit' => 5000]);
$categories = $categoryModel->getAll(true);
$services = $serviceModel->getAll(true);
$pages = $pageModel->getAll(true);

function sitemapUrl($baseUrl, $path, $lastmod = null, $changefreq = 'weekly', $priority = '0.8') {
    $loc = $baseUrl . '/' . ltrim($path, '/');
    $lastmod = $lastmod ? date('Y-m-d', is_numeric($lastmod) ? $lastmod : strtotime($lastmod)) : date('Y-m-d');
    echo '<url>';
    echo '<loc>' . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . '</loc>';
    echo '<lastmod>' . $lastmod . '</lastmod>';
    echo '<changefreq>' . $changefreq . '</changefreq>';
    echo '<priority>' . $priority . '</priority>';
    echo '</url>';
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// Homepage
sitemapUrl($baseUrl, '', null, 'daily', '1.0');

// Static pages
$staticPages = [
    'products.php' => ['changefreq' => 'daily', 'priority' => '0.9'],
    'contact.php' => ['changefreq' => 'monthly', 'priority' => '0.7'],
    'about-us.php' => ['changefreq' => 'monthly', 'priority' => '0.8'],
    'services.php' => ['changefreq' => 'weekly', 'priority' => '0.8'],
    'mission-vision.php' => ['changefreq' => 'monthly', 'priority' => '0.6'],
    'mission.php' => ['changefreq' => 'monthly', 'priority' => '0.6'],
    'vision.php' => ['changefreq' => 'monthly', 'priority' => '0.6'],
    'ceo-message.php' => ['changefreq' => 'monthly', 'priority' => '0.6'],
    'faq.php' => ['changefreq' => 'monthly', 'priority' => '0.7'],
    'blog.php' => ['changefreq' => 'weekly', 'priority' => '0.7'],
    'quote.php' => ['changefreq' => 'monthly', 'priority' => '0.7'],
    'request-catalog.php' => ['changefreq' => 'monthly', 'priority' => '0.6'],
    'testimonials.php' => ['changefreq' => 'monthly', 'priority' => '0.6'],
];
foreach ($staticPages as $file => $opts) {
    sitemapUrl($baseUrl, $file, null, $opts['changefreq'], $opts['priority']);
}

// Categories
foreach ($categories as $category) {
    sitemapUrl($baseUrl, 'products.php?category=' . rawurlencode($category['slug']), $category['updated_at'] ?? null, 'weekly', '0.8');
}

// Products
foreach ($products as $product) {
    sitemapUrl($baseUrl, 'product.php?slug=' . rawurlencode($product['slug']), $product['updated_at'] ?? null, 'weekly', '0.8');
}

// Services
foreach ($services as $service) {
    sitemapUrl($baseUrl, 'service.php?slug=' . rawurlencode($service['slug']), $service['updated_at'] ?? null, 'monthly', '0.7');
}

// CMS pages
foreach ($pages as $page) {
    sitemapUrl($baseUrl, 'page.php?slug=' . rawurlencode($page['slug']), $page['updated_at'] ?? null, 'monthly', '0.7');
}

echo '</urlset>';

} catch (Throwable $e) {
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    $base = rtrim(config('app.url', '') ?: (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
    echo '<url><loc>' . htmlspecialchars($base, ENT_XML1, 'UTF-8') . '</loc><lastmod>' . date('Y-m-d') . '</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>';
    echo '</urlset>';
}

