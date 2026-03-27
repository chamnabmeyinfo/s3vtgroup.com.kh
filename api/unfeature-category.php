<?php
/**
 * Unfeature All Products in Category API
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
use App\Models\Category;

$productModel = new Product();
$categoryModel = new Category();
$response = ['success' => false, 'message' => '', 'count' => 0];

// Get category slug or ID
$categorySlug = $_POST['category_slug'] ?? $_GET['category_slug'] ?? '';
$categoryId = (int)($_POST['category_id'] ?? $_GET['category_id'] ?? 0);

// If slug provided, get category ID
if (!empty($categorySlug) && $categoryId <= 0) {
    $category = $categoryModel->getBySlug($categorySlug);
    if ($category) {
        $categoryId = $category['id'];
    }
}

if ($categoryId <= 0) {
    $response['message'] = 'Invalid category.';
    echo json_encode($response);
    exit;
}

try {
    // Get all products in this category (including subcategories)
    $categoryModel = new Category();
    $descendants = $categoryModel->getDescendants($categoryId, false);
    $descendants[] = $categoryId; // Include the category itself
    $descendants = array_filter($descendants);
    $descendants = array_unique($descendants);
    
    // Build placeholders for IN clause
    $placeholders = [];
    $params = [];
    foreach ($descendants as $idx => $catId) {
        $key = 'cat_id_' . $idx;
        $placeholders[] = ':' . $key;
        $params[$key] = $catId;
    }
    
    // Get category name for response
    $category = $categoryModel->getById($categoryId);
    $categoryName = $category['name'] ?? 'Category';
    
    // Update all products in this category (and subcategories) to unfeatured
    $sql = "UPDATE products SET is_featured = 0 WHERE category_id IN (" . implode(',', $placeholders) . ")";
    db()->query($sql, $params);
    
    // Get count of updated products
    $countSql = "SELECT COUNT(*) as total FROM products WHERE category_id IN (" . implode(',', $placeholders) . ")";
    $result = db()->fetchOne($countSql, $params);
    $count = (int)($result['total'] ?? 0);
    
    $response['success'] = true;
    $response['message'] = "Successfully unfeatured {$count} product(s) in '{$categoryName}' category.";
    $response['count'] = $count;
    $response['category_name'] = $categoryName;
    
} catch (\Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
