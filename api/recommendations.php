<?php
/**
 * Product Recommendations API
 * Returns recommended products based on various criteria
 */
require_once __DIR__ . '/../bootstrap/app.php';

use App\Models\Product;

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

$productId = (int)($_GET['product_id'] ?? 0);
$type = $_GET['type'] ?? 'related'; // related, viewed, popular

$productModel = new Product();

$recommendations = [];

try {
    if ($type === 'related' && $productId) {
        // Get related products (same category, exclude current)
        $currentProduct = $productModel->getById($productId);
        if ($currentProduct) {
            $products = $productModel->getAll([
                'category_id' => $currentProduct['category_id'],
                'limit' => 4,
                'exclude_id' => $productId
            ]);
            $recommendations = array_slice($products, 0, 4);
        }
    } elseif ($type === 'popular') {
        // Get most viewed products
        $products = $productModel->getAll(['limit' => 8, 'order_by' => 'view_count DESC']);
        $recommendations = $products;
    } elseif ($type === 'featured') {
        // Get featured products
        $recommendations = $productModel->getFeatured(8);
    } else {
        // Default: get featured products
        $recommendations = $productModel->getFeatured(8);
    }
    
    echo json_encode([
        'success' => true,
        'products' => $recommendations
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching recommendations'
    ]);
}

