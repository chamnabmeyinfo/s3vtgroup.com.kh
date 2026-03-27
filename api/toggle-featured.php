<?php
/**
 * Toggle Product Featured Status API
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

// Get product ID
$productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);

if ($productId <= 0) {
    $response['message'] = 'Invalid product ID.';
    echo json_encode($response);
    exit;
}

try {
    // Get current product
    $product = $productModel->getById($productId);
    
    if (!$product) {
        $response['message'] = 'Product not found.';
        echo json_encode($response);
        exit;
    }
    
    // Toggle featured status
    $newFeaturedStatus = $product['is_featured'] ? 0 : 1;
    
    // Prepare update data
    $updateData = [
        'is_featured' => $newFeaturedStatus
    ];
    
    // If featuring a product, set it to the next available order (last position)
    // This ensures the first clicked product stays at order 0, and subsequent products get 1, 2, 3, etc.
    if ($newFeaturedStatus == 1) {
        // Get the maximum featured_order value from other featured products
        $maxOrder = db()->fetchOne("SELECT MAX(featured_order) as max_order FROM products WHERE is_featured = 1 AND id != :id", ['id' => $productId]);
        $maxOrderValue = $maxOrder && isset($maxOrder['max_order']) && $maxOrder['max_order'] !== null ? (int)$maxOrder['max_order'] : -1;
        
        // Set new featured product to appear last (after all existing featured products)
        // First product gets 0, second gets 1, third gets 2, etc.
        $newOrder = $maxOrderValue + 1;
        $updateData['featured_order'] = $newOrder;
    }
    
    $productModel->update($productId, $updateData);
    
    $response['success'] = true;
    $response['message'] = $newFeaturedStatus ? 'Product marked as featured.' : 'Product unmarked as featured.';
    $response['is_featured'] = (bool)$newFeaturedStatus;
    if ($newFeaturedStatus == 1) {
        $response['featured_order'] = $updateData['featured_order'];
    }
    
} catch (\Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
