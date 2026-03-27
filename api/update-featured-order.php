<?php
/**
 * Update Featured Order API
 * Only accessible to admin users
 */
require_once __DIR__ . '/../bootstrap/app.php';

header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!session('admin_logged_in')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Admin access required.'
    ]);
    exit;
}

use App\Models\Product;

$productModel = new Product();
$response = ['success' => false, 'message' => ''];

// Get product ID and featured_order
$productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
$featuredOrder = (int)($_POST['featured_order'] ?? $_GET['featured_order'] ?? 0);

if ($productId <= 0) {
    $response['message'] = 'Invalid product ID.';
    echo json_encode($response);
    exit;
}

// Validate featured_order (must be >= 0)
if ($featuredOrder < 0) {
    $featuredOrder = 0;
}

try {
    // Get current product
    $product = $productModel->getById($productId);
    
    if (!$product) {
        $response['message'] = 'Product not found.';
        echo json_encode($response);
        exit;
    }
    
    // Update featured_order
    $productModel->update($productId, [
        'featured_order' => $featuredOrder
    ]);
    
    $response['success'] = true;
    $response['message'] = 'Featured order updated successfully.';
    $response['featured_order'] = $featuredOrder;
    
} catch (\Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
