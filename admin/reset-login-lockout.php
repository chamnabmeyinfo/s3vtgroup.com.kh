<?php
/**
 * Reset Login Lockout
 * This script clears the failed login attempts counter
 * 
 * WARNING: Remove this file after resetting!
 */

require_once __DIR__ . '/../bootstrap/app.php';

// Clear login attempts
session('login_attempts', 0);
session('last_attempt', 0);

// Also clear developer session attempts if they exist
session_name('developer_session');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['login_attempts'] = 0;
$_SESSION['last_attempt'] = 0;

// Switch back to admin session
session_name('admin_session');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Lockout Reset - ForkliftPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8 text-center">
        <div class="mb-6">
            <div class="inline-block bg-green-100 rounded-full p-4 mb-4">
                <i class="fas fa-check-circle text-5xl text-green-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Login Lockout Reset</h1>
            <p class="text-gray-600 mb-6">Your login attempts have been cleared. You can now try logging in again.</p>
        </div>
        
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 text-left">
            <p class="text-sm text-green-800">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Status:</strong> Login attempts counter has been reset to 0.
            </p>
        </div>
        
        <div class="space-y-3">
            <a href="<?= url('admin/login.php') ?>" 
               class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-all transform hover:scale-105">
                <i class="fas fa-sign-in-alt mr-2"></i>
                Go to Login Page
            </a>
            
            <p class="text-xs text-gray-500 mt-4">
                <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>
                <strong>Security Note:</strong> Please delete this file after use!
            </p>
        </div>
    </div>
    
    <script>
        // Auto-redirect after 3 seconds
        setTimeout(function() {
            window.location.href = '<?= url('admin/login.php') ?>';
        }, 3000);
    </script>
</body>
</html>
