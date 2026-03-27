<?php
require_once __DIR__ . '/../bootstrap/app.php';

// Redirect if already logged in
if (session('admin_logged_in')) {
    header('Location: ' . url('admin/index.php'));
    exit;
}

$message = '';
$error = '';
$token = $_GET['token'] ?? '';
$validToken = false;
$userId = null;

// Validate token
if (!empty($token)) {
    try {
        $tokenData = db()->fetchOne(
            "SELECT prt.*, u.email, u.name 
             FROM password_reset_tokens prt
             INNER JOIN admin_users u ON prt.user_id = u.id
             WHERE prt.token = :token 
             AND prt.used = 0 
             AND prt.expires_at > NOW()",
            ['token' => $token]
        );
        
        if ($tokenData) {
            $validToken = true;
            $userId = $tokenData['user_id'];
        } else {
            $error = 'Invalid or expired reset token. Please request a new password reset.';
        }
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), "doesn't exist") !== false) {
            $error = 'Password reset feature is not set up. Please contact administrator.';
        } else {
            $error = 'Error validating token. Please try again.';
        }
    }
} else {
    $error = 'No reset token provided.';
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all fields.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        try {
            // Update password
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            db()->update('admin_users', 
                ['password' => $passwordHash], 
                'id = :id', 
                ['id' => $userId]
            );
            
            // Mark token as used
            db()->query(
                "UPDATE password_reset_tokens SET used = 1 WHERE token = :token",
                ['token' => $token]
            );
            
            $message = 'Password has been reset successfully! You can now login with your new password.';
            $validToken = false; // Prevent form from showing again
            
            // Redirect to login after 3 seconds
            header('refresh:3;url=' . url('admin/login.php'));
        } catch (\Exception $e) {
            $error = 'Error resetting password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Reset Password
                </h2>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <?= escape($message) ?>
                    <p class="mt-2 text-sm">Redirecting to login page...</p>
                </div>
            <?php elseif ($validToken): ?>
                <form class="mt-8 space-y-6 bg-white p-8 rounded-lg shadow-md" method="POST">
                    <?php if (!empty($error)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            <?= escape($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <input id="new_password" name="new_password" type="password" required minlength="6"
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Must be at least 6 characters</p>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input id="confirm_password" name="confirm_password" type="password" required minlength="6"
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Reset Password
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <?php if (!empty($error)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?= escape($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center">
                        <a href="<?= url('admin/forgot-password.php') ?>" class="text-blue-600 hover:text-blue-800">
                            Request New Reset Link
                        </a>
                        <span class="mx-2">|</span>
                        <a href="<?= url('admin/login.php') ?>" class="text-blue-600 hover:text-blue-800">
                            Back to Login
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

