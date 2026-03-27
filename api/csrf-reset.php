<?php
/**
 * CSRF Token Reset API
 * Regenerates CSRF token and returns new token value
 */
require_once __DIR__ . '/../bootstrap/app.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} else {
    // Regenerate token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo json_encode([
    'success' => true,
    'token' => $_SESSION['csrf_token'],
    'message' => 'CSRF token reset successfully'
]);
