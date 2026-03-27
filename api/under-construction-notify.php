<?php
/**
 * Under Construction Email Notification API
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../bootstrap/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

try {
    $db = db();
    
    // Check if table exists, if not create it
    try {
        $db->fetchOne("SELECT 1 FROM construction_notifications LIMIT 1");
    } catch (Exception $e) {
        // Table doesn't exist, create it
        $db->getPdo()->exec("CREATE TABLE IF NOT EXISTS construction_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_email (email),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    // Insert email (will fail silently if duplicate due to UNIQUE constraint)
    // Use get_real_ip() helper function if available (works with Cloudflare)
    $ipAddress = function_exists('get_real_ip') ? get_real_ip() : ($_SERVER['REMOTE_ADDR'] ?? null);
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    try {
        $db->insert('construction_notifications', [
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ]);
        
        // Optional: Send email notification to admin
        // You can implement email sending here if needed
        
        echo json_encode([
            'success' => true,
            'message' => 'Thank you! We\'ll notify you when we launch.'
        ]);
    } catch (\PDOException $e) {
        // If duplicate email, still return success
        if ($e->getCode() == 23000) { // Duplicate entry
            echo json_encode([
                'success' => true,
                'message' => 'You\'re already on our notification list!'
            ]);
        } else {
            throw $e;
        }
    }
    
} catch (Exception $e) {
    error_log('Construction notification error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Unable to process request. Please try again later.'
    ]);
}

