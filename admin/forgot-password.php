<?php
require_once __DIR__ . '/../bootstrap/app.php';

// Redirect if already logged in
if (session('admin_logged_in')) {
    header('Location: ' . url('admin/index.php'));
    exit;
}

use App\Helpers\EmailHelper;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Find user by email
            $user = db()->fetchOne(
                "SELECT * FROM admin_users WHERE email = :email AND is_active = 1",
                ['email' => $email]
            );
            
            if ($user) {
                // Generate secure token
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Invalidate any existing tokens for this user
                try {
                    db()->query(
                        "UPDATE password_reset_tokens SET used = 1 WHERE user_id = :user_id AND used = 0",
                        ['user_id' => $user['id']]
                    );
                } catch (\Exception $e) {
                    // Table might not exist yet, continue
                }
                
                // Create new token
                try {
                    db()->insert('password_reset_tokens', [
                        'user_id' => $user['id'],
                        'token' => $token,
                        'expires_at' => $expiresAt,
                        'used' => 0
                    ]);
                    
                    // Send password reset email
                    $resetUrl = url('admin/reset-password.php?token=' . $token);
                    $siteName = config('app.name', 'Admin Panel');
                    
                    $emailBody = "
                    <h2>Password Reset Request</h2>
                    <p>Hello {$user['name']},</p>
                    <p>You have requested to reset your password for your {$siteName} account.</p>
                    <p>Click the link below to reset your password:</p>
                    <p><a href=\"{$resetUrl}\" style=\"display:inline-block;padding:10px 20px;background:#3b82f6;color:#fff;text-decoration:none;border-radius:5px;\">Reset Password</a></p>
                    <p>Or copy and paste this URL into your browser:</p>
                    <p style=\"word-break:break-all;\">{$resetUrl}</p>
                    <p><strong>This link will expire in 1 hour.</strong></p>
                    <p>If you did not request this password reset, please ignore this email.</p>
                    <p>Best regards,<br>{$siteName} Team</p>
                    ";
                    
                    if (EmailHelper::sendPasswordReset($user['email'], $emailBody)) {
                        $message = 'Password reset link has been sent to your email address. Please check your inbox.';
                    } else {
                        $error = 'Failed to send email. Please try again later or contact administrator.';
                    }
                } catch (\Exception $e) {
                    // Check if table doesn't exist
                    if (strpos($e->getMessage(), "doesn't exist") !== false) {
                        $error = 'Password reset feature is not set up. Please run the database migration first.';
                    } else {
                        $error = 'Error creating reset token. Please try again.';
                    }
                }
            } else {
                // Don't reveal if email exists for security
                $message = 'If an account with that email exists, a password reset link has been sent.';
            }
        } catch (\Exception $e) {
            $error = 'An error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Forgot Password
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Enter your email address and we'll send you a link to reset your password.
                </p>
            </div>
            <form class="mt-8 space-y-6 bg-white p-8 rounded-lg shadow-md" method="POST">
                <?php if (!empty($message)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        <?= escape($message) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <?= escape($error) ?>
                    </div>
                <?php endif; ?>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input id="email" name="email" type="email" required
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="your.email@example.com">
                </div>
                
                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Send Reset Link
                    </button>
                </div>
                
                <div class="text-center">
                    <a href="<?= url('admin/login.php') ?>" class="text-sm text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

