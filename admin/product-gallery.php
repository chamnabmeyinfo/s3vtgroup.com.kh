<?php
/**
 * Product Gallery Manager - AJAX endpoint
 */
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Product;

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);
    
    if (!$productId) {
        $response['message'] = 'Invalid product ID.';
        echo json_encode($response);
        exit;
    }
    
    $productModel = new Product();
    $product = $productModel->getById($productId);
    
    if (!$product) {
        $response['message'] = 'Product not found.';
        echo json_encode($response);
        exit;
    }
    
    $gallery = [];
    if (!empty($product['gallery'])) {
        $gallery = json_decode($product['gallery'], true) ?? [];
    }
    
    switch ($action) {
        case 'add':
            $image = trim($_POST['image'] ?? '');
            if ($image && !in_array($image, $gallery)) {
                $gallery[] = $image;
                $productModel->update($productId, ['gallery' => json_encode($gallery)]);
                $response['success'] = true;
                $response['message'] = 'Image added to gallery.';
                $response['gallery'] = $gallery;
            } else {
                $response['message'] = 'Image already in gallery or invalid.';
            }
            break;
            
        case 'remove':
            $image = trim($_POST['image'] ?? '');
            $gallery = array_values(array_filter($gallery, fn($img) => $img !== $image));
            $productModel->update($productId, ['gallery' => json_encode($gallery)]);
            $response['success'] = true;
            $response['message'] = 'Image removed from gallery.';
            $response['gallery'] = $gallery;
            break;
            
        case 'reorder':
            $order = $_POST['order'] ?? [];
            if (is_array($order)) {
                $orderedGallery = [];
                foreach ($order as $img) {
                    if (in_array($img, $gallery)) {
                        $orderedGallery[] = $img;
                    }
                }
                // Add any missing images
                foreach ($gallery as $img) {
                    if (!in_array($img, $orderedGallery)) {
                        $orderedGallery[] = $img;
                    }
                }
                $productModel->update($productId, ['gallery' => json_encode($orderedGallery)]);
                $response['success'] = true;
                $response['message'] = 'Gallery order updated.';
                $response['gallery'] = $orderedGallery;
            }
            break;
            
        default:
            $response['message'] = 'Invalid action.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);

