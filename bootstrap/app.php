<?php

// Configure secure session settings BEFORE starting session
// IMPORTANT: All session cookie settings MUST be set BEFORE session_start() is called

// Configure session cookie to work with both www and non-www domains
// Set cookie domain to work across subdomains (e.g., www.s3vtgroup.com.kh and s3vtgroup.com.kh)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// For production domains, set cookie to work for both www and non-www
if (strpos($host, 's3vtgroup.com.kh') !== false) {
    // Extract the base domain
    $parts = explode('.', $host);
    if (count($parts) >= 3) {
        // Domain like www.s3vtgroup.com.kh or s3vtgroup.com.kh
        $domain = '.' . implode('.', array_slice($parts, -3)); // .s3vtgroup.com.kh
        ini_set('session.cookie_domain', $domain);
    }
}

// Security: Set secure session cookie parameters
ini_set('session.cookie_httponly', 1);
// Use 'Lax' instead of 'Strict' to allow cookies to work across www/non-www subdomains
// while still providing CSRF protection for cross-site requests
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_lifetime', 0); // Session cookie (expires on browser close)

// Only set secure flag if using HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

// Start session
// Note: Session name should be set BEFORE this file is included if you want a custom name
// Developer pages set session_name('developer_session') before including this file
// Skip session start if headers already sent (e.g., during deployment)
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

// Load helper functions
require_once __DIR__ . '/../app/Support/functions.php';

// Autoloader (must be registered first so classes can be autoloaded)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load Hook Manager (needed for post types)
require_once __DIR__ . '/../app/Hooks/HookManager.php';

// Load Post Type Registry (needed before post-types.php)
require_once __DIR__ . '/../app/Registry/PostTypeRegistry.php';

// Register default post types
require_once __DIR__ . '/../app/Support/post-types.php';

// Error reporting
if (config('app.debug', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL); // Still log all errors
    ini_set('display_errors', 0); // But don't display them
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../storage/logs/php_errors.log');
}

// Security: Prevent information disclosure in error messages
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    // Log the error
    error_log(sprintf("[%s] %s in %s on line %d", 
        date('Y-m-d H:i:s'), 
        $message, 
        $file, 
        $line
    ));
    
    // In production, show generic error
    if (!config('app.debug', false)) {
        if ($severity === E_ERROR || $severity === E_USER_ERROR) {
            http_response_code(500);
            die('An error occurred. Please contact support if this persists.');
        }
    }
    
    return false; // Let PHP handle it normally in debug mode
});

