<?php
/**
 * Shopping Cart API
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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$productId = (int)($_GET['product_id'] ?? $_POST['product_id'] ?? 0);

// Security: Limit cart size to prevent DoS
$maxCartItems = 100;
if (count($_SESSION['cart']) >= $maxCartItems && $action === 'add') {
    echo json_encode(['success' => false, 'message' => 'Cart is full. Maximum ' . $maxCartItems . ' items allowed.', 'count' => count($_SESSION['cart'])]);
    exit;
}

$response = ['success' => false, 'message' => '', 'count' => count($_SESSION['cart'])];

switch ($action) {
    case 'add':
        if ($productId > 0) {
            // Security: Validate product exists and is active
            try {
                $product = db()->fetchOne("SELECT id FROM products WHERE id = :id AND is_active = 1", ['id' => $productId]);
                if (!$product) {
                    $response['message'] = 'Invalid or inactive product.';
                    break;
                }
            } catch (Exception $e) {
                $response['message'] = 'Error validating product.';
                break;
            }
            
            if (!isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId] = 0;
            }
            // Security: Limit quantity per item
            $maxQuantity = 999;
            if ($_SESSION['cart'][$productId] >= $maxQuantity) {
                $response['message'] = 'Maximum quantity reached for this item.';
                break;
            }
            $_SESSION['cart'][$productId]++;
            $response['success'] = true;
            $response['message'] = 'Product added to cart';
            $response['count'] = array_sum($_SESSION['cart']);
        }
        break;
        
    case 'update':
        $quantity = (int)($_GET['quantity'] ?? $_POST['quantity'] ?? 1);
        // Security: Validate and limit quantity
        $quantity = max(1, min(999, $quantity));
        
        if ($productId > 0) {
            // Security: Validate product exists
            try {
                $product = db()->fetchOne("SELECT id FROM products WHERE id = :id AND is_active = 1", ['id' => $productId]);
                if (!$product) {
                    $response['message'] = 'Invalid or inactive product.';
                    break;
                }
            } catch (Exception $e) {
                $response['message'] = 'Error validating product.';
                break;
            }
            
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$productId]);
            } else {
                $_SESSION['cart'][$productId] = $quantity;
            }
            $response['success'] = true;
            $response['count'] = array_sum($_SESSION['cart']);
        }
        break;
        
    case 'remove':
        if ($productId) {
            unset($_SESSION['cart'][$productId]);
            $response['success'] = true;
            $response['message'] = 'Product removed from cart';
            $response['count'] = array_sum($_SESSION['cart']);
        }
        break;
        
    case 'clear':
        $_SESSION['cart'] = [];
        $response['success'] = true;
        $response['message'] = 'Cart cleared';
        $response['count'] = 0;
        break;
        
    case 'count':
        $response['success'] = true;
        $response['count'] = array_sum($_SESSION['cart']);
        break;
        
    default:
        $response['cart'] = $_SESSION['cart'];
        $response['success'] = true;
}

// PHP will automatically write session on script end, but force it here to ensure data is saved
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

echo json_encode($response);

