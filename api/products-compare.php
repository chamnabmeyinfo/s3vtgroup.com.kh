<?php
/**
 * Product Comparison API
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

$productIds = $_GET['ids'] ?? '';
$ids = array_filter(array_map('intval', explode(',', $productIds)));

if (empty($ids) || count($ids) > 4) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid product IDs']);
    exit;
}

$productModel = new Product();
$products = [];

foreach ($ids as $id) {
    $product = $productModel->getById($id);
    if ($product && $product['is_active']) {
        $products[] = $product;
    }
}

header('Content-Type: application/json');
echo json_encode(['products' => $products]);

