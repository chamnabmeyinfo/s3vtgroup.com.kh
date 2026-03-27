<?php
/**
 * Smart Search API
 */
require_once __DIR__ . '/../bootstrap/app.php';

use App\Services\SmartSearch;

// CORS headers
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'https://www.s3vtgroup.com.kh',
    'https://s3vtgroup.com.kh',
    'http://localhost',
    'http://127.0.0.1'
];

if (in_array($origin, $allowedOrigins) || strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'search';
$query = trim($_GET['q'] ?? $_GET['query'] ?? '');
$categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;

$searchService = new SmartSearch();

try {
    if ($action === 'autocomplete') {
        $suggestions = $searchService->autocomplete($query, 10);
        echo json_encode([
            'success' => true,
            'suggestions' => $suggestions
        ]);
    } else {
        $results = $searchService->search($query, [
            'category_id' => $categoryId,
            'limit' => 20
        ]);
        
        // Format products
        $products = [];
        foreach ($results['products'] as $product) {
            $products[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'slug' => $product['slug'],
                'price' => $product['price'],
                'sale_price' => $product['sale_price'] ?? null,
                'image' => $product['image'] ?? null,
                'short_description' => $product['short_description'] ?? '',
                'category_name' => $product['category_name'] ?? '',
                'url' => url('product.php?slug=' . $product['slug'])
            ];
        }
        
        echo json_encode([
            'success' => true,
            'products' => $products,
            'suggestions' => $results['suggestions'],
            'related' => $results['related'],
            'count' => count($products)
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Search error'
    ]);
}

