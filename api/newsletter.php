<?php
/**
 * Newsletter Subscription API
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

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $action = $_POST['action'] ?? 'subscribe';
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address.';
        echo json_encode($response);
        exit;
    }
    
    try {
        if ($action === 'subscribe') {
            $existing = db()->fetchOne("SELECT id FROM newsletter_subscribers WHERE email = :email", ['email' => $email]);
            
            if ($existing) {
                db()->update('newsletter_subscribers', 
                    ['status' => 'active', 'name' => $name], 
                    'email = :email', 
                    ['email' => $email]
                );
                $response['success'] = true;
                $response['message'] = 'You are already subscribed!';
            } else {
                db()->insert('newsletter_subscribers', [
                    'email' => $email,
                    'name' => $name,
                    'status' => 'active'
                ]);
                $response['success'] = true;
                $response['message'] = 'Successfully subscribed to newsletter!';
            }
        } elseif ($action === 'unsubscribe') {
            db()->update('newsletter_subscribers', 
                ['status' => 'unsubscribed', 'unsubscribed_at' => date('Y-m-d H:i:s')], 
                'email = :email', 
                ['email' => $email]
            );
            $response['success'] = true;
            $response['message'] = 'Successfully unsubscribed.';
        }
    } catch (Exception $e) {
        $response['message'] = 'Error processing request. Please try again.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);

