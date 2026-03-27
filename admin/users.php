<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Role;
use App\Helpers\EmailHelper;

requirePermission('view_users');

$message = '';
$error = '';

// Handle delete
if (!empty($_GET['delete']) && hasPermission('delete_users')) {
    $userId = (int)$_GET['delete'];
    $currentUserId = session('admin_user_id');
    
    if ($userId === $currentUserId) {
        $error = 'You cannot delete your own account.';
    } else {
        db()->delete('admin_users', 'id = :id', ['id' => $userId]);
        $message = 'User deleted successfully.';
    }
}

// Handle toggle active
if (!empty($_GET['toggle_active']) && hasPermission('edit_users')) {
    $userId = (int)$_GET['toggle_active'];
    $currentUserId = session('admin_user_id');
    
    if ($userId === $currentUserId) {
        $error = 'You cannot deactivate your own account.';
    } else {
        $user = db()->fetchOne("SELECT is_active FROM admin_users WHERE id = :id", ['id' => $userId]);
        if ($user) {
            db()->update('admin_users', 
                ['is_active' => $user['is_active'] ? 0 : 1],
                'id = :id',
                ['id' => $userId]
            );
            $message = 'User status updated successfully.';
        }
    }
}

// Handle send password reset
if (!empty($_GET['send_reset']) && hasPermission('edit_users')) {
    $userId = (int)$_GET['send_reset'];
    
    try {
        $user = db()->fetchOne("SELECT * FROM admin_users WHERE id = :id", ['id' => $userId]);
        
        if ($user && !empty($user['email'])) {
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
                // Table might not exist yet
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
                <p>A password reset has been initiated for your {$siteName} account.</p>
                <p>Click the link below to reset your password:</p>
                <p><a href=\"{$resetUrl}\" style=\"display:inline-block;padding:10px 20px;background:#3b82f6;color:#fff;text-decoration:none;border-radius:5px;\">Reset Password</a></p>
                <p>Or copy and paste this URL into your browser:</p>
                <p style=\"word-break:break-all;\">{$resetUrl}</p>
                <p><strong>This link will expire in 1 hour.</strong></p>
                <p>If you did not request this password reset, please contact your administrator immediately.</p>
                <p>Best regards,<br>{$siteName} Team</p>
                ";
                
                if (EmailHelper::sendPasswordReset($user['email'], $emailBody)) {
                    $message = 'Password reset email has been sent to ' . escape($user['email']) . '.';
                } else {
                    $error = 'Failed to send email. Please try again later.';
                }
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), "doesn't exist") !== false) {
                    $error = 'Password reset feature is not set up. Please run the database migration first.';
                } else {
                    $error = 'Error creating reset token: ' . $e->getMessage();
                }
            }
        } else {
            $error = 'User not found or email address is missing.';
        }
    } catch (\Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get all users with their roles
$users = db()->fetchAll(
    "SELECT u.*, r.name as role_name, r.slug as role_slug 
     FROM admin_users u 
     LEFT JOIN roles r ON u.role_id = r.id 
     ORDER BY u.created_at DESC"
);

$pageTitle = 'Users';
include __DIR__ . '/includes/header.php';
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Admin Users</h1>
        <?php if (hasPermission('create_users')): ?>
        <a href="<?= url('admin/user-edit.php') ?>" class="btn-primary">
            <i class="fas fa-plus mr-2"></i> Add New User
        </a>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?= escape($message) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= escape($error) ?>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Login</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($users as $user): ?>
                    <?php $isCurrentUser = $user['id'] == session('admin_user_id'); ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            <?= escape($user['username']) ?>
                            <?php if ($isCurrentUser): ?>
                                <span class="text-xs text-blue-600">(You)</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= escape($user['name'] ?? '-') ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= escape($user['email']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($user['role_name']): ?>
                            <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">
                                <?= escape($user['role_name']) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-xs text-gray-400">No Role</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded <?= $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                            <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never' ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <?php if (hasPermission('edit_users')): ?>
                        <a href="<?= url('admin/user-edit.php?id=' . $user['id']) ?>" 
                           class="text-blue-600 hover:text-blue-900" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('edit_users') && !empty($user['email'])): ?>
                        <a href="?send_reset=<?= $user['id'] ?>" 
                           onclick="return confirm('Send password reset email to <?= escape($user['email']) ?>?')" 
                           class="text-purple-600 hover:text-purple-900" title="Send Password Reset">
                            <i class="fas fa-key"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('edit_users') && !$isCurrentUser): ?>
                        <a href="?toggle_active=<?= $user['id'] ?>" 
                           class="text-yellow-600 hover:text-yellow-900" title="Toggle Status">
                            <i class="fas fa-toggle-<?= $user['is_active'] ? 'on' : 'off' ?>"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('delete_users') && !$isCurrentUser): ?>
                        <a href="?delete=<?= $user['id'] ?>" 
                           onclick="return confirm('Are you sure you want to delete this user?')" 
                           class="text-red-600 hover:text-red-900" title="Delete">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

