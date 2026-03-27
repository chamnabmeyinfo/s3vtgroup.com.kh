<?php
/**
 * Advanced Search API with Autocomplete
 */
require_once __DIR__ . '/../bootstrap/app.php';

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

$query = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'products'; // products, categories, all

if (empty($query) || strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$results = [];

if ($type === 'products' || $type === 'all') {
    $searchTerm = "%{$query}%";
    $exactTerm = $query;
    $startTerm = "{$query}%";
    
    $products = db()->fetchAll(
        "SELECT id, name, slug, price, image, short_description 
         FROM products 
         WHERE is_active = 1 
         AND (name LIKE :query_name OR short_description LIKE :query_short OR description LIKE :query_desc)
         ORDER BY 
           CASE 
             WHEN name LIKE :exact THEN 1
             WHEN name LIKE :start THEN 2
             ELSE 3
           END,
           name ASC
         LIMIT 10",
        [
            'query_name' => $searchTerm,
            'query_short' => $searchTerm,
            'query_desc' => $searchTerm,
            'exact' => $exactTerm,
            'start' => $startTerm
        ]
    );
    
    foreach ($products as $product) {
        $results[] = [
            'type' => 'product',
            'id' => $product['id'],
            'title' => $product['name'],
            'url' => url('product.php?slug=' . $product['slug']),
            'price' => '$' . number_format($product['price'], 2),
            'image' => !empty($product['image']) ? asset('storage/uploads/' . $product['image']) : null,
            'description' => substr($product['short_description'] ?? '', 0, 100)
        ];
    }
}

if ($type === 'categories' || $type === 'all') {
    $categorySearchTerm = "%{$query}%";
    $categories = db()->fetchAll(
        "SELECT id, name, slug, description 
         FROM categories 
         WHERE is_active = 1 
         AND (name LIKE :cat_query_name OR description LIKE :cat_query_desc)
         LIMIT 5",
        [
            'cat_query_name' => $categorySearchTerm,
            'cat_query_desc' => $categorySearchTerm
        ]
    );
    
    foreach ($categories as $category) {
        $results[] = [
            'type' => 'category',
            'id' => $category['id'],
            'title' => $category['name'],
            'url' => url('products.php?category=' . $category['slug']),
            'description' => substr($category['description'] ?? '', 0, 100)
        ];
    }
}

echo json_encode(['results' => $results]);

