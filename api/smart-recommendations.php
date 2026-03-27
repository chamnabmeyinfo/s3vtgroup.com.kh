<?php
/**
 * Smart Recommendations API
 */
require_once __DIR__ . '/../bootstrap/app.php';

use App\Services\SmartRecommendations;

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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userIdentifier = $_GET['user_id'] ?? session_id();
$limit = (int)($_GET['limit'] ?? 8);
$type = $_GET['type'] ?? 'all';

$recommendationService = new SmartRecommendations();

try {
    $recommendations = $recommendationService->getRecommendations($userIdentifier, $limit);
    
    // Format response
    $products = [];
    foreach ($recommendations as $rec) {
        $product = $rec['product'];
        $products[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'slug' => $product['slug'],
            'price' => $product['price'],
            'sale_price' => $product['sale_price'] ?? null,
            'image' => $product['image'] ?? null,
            'short_description' => $product['short_description'] ?? '',
            'recommendation_type' => $rec['type'],
            'score' => $rec['score'],
            'url' => url('product.php?slug=' . $product['slug'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching recommendations'
    ]);
}

