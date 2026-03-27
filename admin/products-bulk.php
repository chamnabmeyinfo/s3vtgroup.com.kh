<?php
/**
 * Bulk Operations Handler
 */
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Product;

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Security: CSRF protection
    require_csrf();
    
    $action = $_POST['action'] ?? '';
    $productIds = $_POST['product_ids'] ?? [];
    
    if (empty($productIds) || !is_array($productIds)) {
        $response['message'] = 'No products selected.';
        echo json_encode($response);
        exit;
    }
    
    // Security: Validate and filter product IDs
    $productIds = array_filter(array_map('intval', $productIds), function($id) {
        return $id > 0;
    });
    
    if (empty($productIds)) {
        $response['message'] = 'No valid products selected.';
        echo json_encode($response);
        exit;
    }
    
    // Security: Limit bulk operations to prevent DoS
    if (count($productIds) > 1000) {
        $response['message'] = 'Too many products selected. Maximum 1000 items per operation.';
        echo json_encode($response);
        exit;
    }
    
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    try {
        switch ($action) {
            case 'delete':
                $productModel = new Product();
                $deletedCount = 0;
                $errors = [];
                
                foreach ($productIds as $productId) {
                    try {
                        $productModel->delete($productId);
                        $deletedCount++;
                    } catch (\Exception $e) {
                        $errors[] = "Product ID {$productId}: " . $e->getMessage();
                    }
                }
                
                if ($deletedCount > 0) {
                    $response['success'] = true;
                    $response['message'] = $deletedCount . ' product(s) deleted successfully.';
                    if (!empty($errors)) {
                        $response['message'] .= ' Some products could not be deleted: ' . implode(', ', $errors);
                    }
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Failed to delete products: ' . implode(', ', $errors);
                }
                break;
                
            case 'activate':
                db()->query("UPDATE products SET is_active = 1 WHERE id IN ($placeholders)", $productIds);
                $response['success'] = true;
                $response['message'] = count($productIds) . ' product(s) activated successfully.';
                break;
                
            case 'deactivate':
                db()->query("UPDATE products SET is_active = 0 WHERE id IN ($placeholders)", $productIds);
                $response['success'] = true;
                $response['message'] = count($productIds) . ' product(s) deactivated successfully.';
                break;
                
            case 'feature':
                db()->query("UPDATE products SET is_featured = 1 WHERE id IN ($placeholders)", $productIds);
                $response['success'] = true;
                $response['message'] = count($productIds) . ' product(s) marked as featured.';
                break;
                
            case 'unfeature':
                db()->query("UPDATE products SET is_featured = 0 WHERE id IN ($placeholders)", $productIds);
                $response['success'] = true;
                $response['message'] = count($productIds) . ' product(s) unmarked as featured.';
                break;
                
            default:
                $response['message'] = 'Invalid action.';
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);

