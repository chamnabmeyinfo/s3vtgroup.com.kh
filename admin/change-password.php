<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

$message = '';
$error = '';
$userId = session('admin_user_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Security: CSRF protection
    require_csrf();
    
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'All fields are required.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } else {
        // Security: Validate password strength
        $passwordValidation = validate_password($newPassword);
        if (!$passwordValidation['valid']) {
            $error = implode(' ', $passwordValidation['errors']);
        }
    }
    
    if (empty($error)) {
        // Verify current password
        $user = db()->fetchOne("SELECT password FROM admin_users WHERE id = :id", ['id' => $userId]);
        
        if ($user && password_verify($currentPassword, $user['password'])) {
            // Update password
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            db()->update('admin_users', ['password' => $passwordHash], 'id = :id', ['id' => $userId]);
            
            $message = 'Password changed successfully.';
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}

$pageTitle = 'Change Password';
include __DIR__ . '/includes/header.php';
?>

<h1 class="text-3xl font-bold mb-6">Change Password</h1>

<form method="POST" class="bg-white rounded-lg shadow p-6 space-y-6 max-w-md">
    <?= csrf_field() ?>
    <div>
        <label class="block text-sm font-medium mb-2">Current Password</label>
        <input type="password" name="current_password" required
               class="w-full px-4 py-2 border rounded-lg">
    </div>
    
    <div>
        <label class="block text-sm font-medium mb-2">New Password</label>
        <input type="password" name="new_password" required minlength="6"
               class="w-full px-4 py-2 border rounded-lg">
        <p class="text-sm text-gray-600 mt-1">Must be at least 12 characters with uppercase, lowercase, number, and special character</p>
    </div>
    
    <div>
        <label class="block text-sm font-medium mb-2">Confirm New Password</label>
        <input type="password" name="confirm_password" required minlength="6"
               class="w-full px-4 py-2 border rounded-lg">
    </div>
    
    <div class="flex space-x-4">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
            Change Password
        </button>
        <a href="<?= url('admin/index.php') ?>" class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400">
            Cancel
        </a>
    </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>

