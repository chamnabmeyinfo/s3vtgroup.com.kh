<?php
/**
 * Product Comparison API
 */
require_once __DIR__ . '/../bootstrap/app.php';

// CORS headers
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'https://www.s3vtgroup.com.kh',
    'https://s3vtgroup.com.kh',
    'https://dev.s3vtgroup.com.kh',
    'http://localhost',
    'http://127.0.0.1'
];

if (in_array($origin, $allowedOrigins) || strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false || strpos($origin, 'dev.s3vtgroup.com.kh') !== false) {
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

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        $productId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($productId) {
            if (!isset($_SESSION['compare'])) {
                $_SESSION['compare'] = [];
            }
            $compare = $_SESSION['compare'];
            if (!in_array($productId, $compare) && count($compare) < 4) {
                $compare[] = $productId;
                $_SESSION['compare'] = $compare;
                $response = ['success' => true, 'count' => count($compare), 'message' => 'Added to comparison'];
            } elseif (count($compare) >= 4) {
                $response = ['success' => false, 'message' => 'Maximum 4 products can be compared'];
            } else {
                $response = ['success' => false, 'message' => 'Already in comparison'];
            }
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        }
        break;
        
    case 'remove':
        $productId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!isset($_SESSION['compare'])) {
            $_SESSION['compare'] = [];
        }
        $compare = $_SESSION['compare'];
        $_SESSION['compare'] = array_values(array_filter($compare, fn($id) => $id != $productId));
        echo json_encode(['success' => true, 'count' => count($_SESSION['compare'])]);
        break;
        
    case 'clear':
        if (!isset($_SESSION['compare'])) {
            $_SESSION['compare'] = [];
        }
        $_SESSION['compare'] = [];
        echo json_encode(['success' => true]);
        break;
        
    case 'get':
        if (!isset($_SESSION['compare'])) {
            $_SESSION['compare'] = [];
        }
        $compare = $_SESSION['compare'];
        echo json_encode([
            'compare' => $compare,
            'count' => count($compare)
        ]);
        break;
        
    case 'count':
        if (!isset($_SESSION['compare'])) {
            $_SESSION['compare'] = [];
        }
        $compare = $_SESSION['compare'];
        echo json_encode(['count' => count($compare)]);
        break;
        
    default:
        if (!isset($_SESSION['compare'])) {
            $_SESSION['compare'] = [];
        }
        $compare = $_SESSION['compare'];
        echo json_encode([
            'compare' => $compare,
            'count' => count($compare)
        ]);
}

// PHP will automatically write session on script end, but force it here to ensure data is saved
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

