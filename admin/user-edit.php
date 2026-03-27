<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Role;

$userId = $_GET['id'] ?? null;
$isEdit = !empty($userId);
$currentUserId = session('admin_user_id');

if ($isEdit) {
    requirePermission('edit_users');
} else {
    requirePermission('create_users');
}

$roleModel = new Role();
$message = '';
$error = '';

// Get user data
$user = null;
if ($isEdit) {
    $user = db()->fetchOne(
        "SELECT u.*, r.id as role_id FROM admin_users u 
         LEFT JOIN roles r ON u.role_id = r.id 
         WHERE u.id = :id",
        ['id' => $userId]
    );
    
    if (!$user) {
        $error = 'User not found.';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $roleId = !empty($_POST['role_id']) ? (int)$_POST['role_id'] : null;
    
    // Handle is_active: if editing own account, always keep it active (disabled checkbox doesn't submit)
    if ($isEdit && $userId == $currentUserId) {
        $isActive = 1; // Always active for own account
    } else {
        $isActive = isset($_POST['is_active']) ? 1 : 0;
    }
    
    if (empty($username) || empty($email)) {
        $error = 'Username and email are required.';
    } else {
        try {
            $updateData = [
                'username' => $username,
                'email' => $email,
                'name' => $name ?: null,
                'role_id' => $roleId,
                'is_active' => $isActive
            ];
            
            if ($isEdit) {
                // Check if username/email already exists (excluding current user)
                $existing = db()->fetchOne(
                    "SELECT id FROM admin_users WHERE (username = :username OR email = :email) AND id != :id",
                    ['username' => $username, 'email' => $email, 'id' => $userId]
                );
                
                if ($existing) {
                    $error = 'Username or email already exists.';
                } else {
                    // Validate and update password if provided
                    if (!empty($password)) {
                        // Validate password length
                        if (strlen($password) < 6) {
                            $error = 'Password must be at least 6 characters long.';
                        } else {
                            $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
                        }
                    }
                    
                    // Only proceed if no error occurred
                    if (empty($error)) {
                        db()->update('admin_users', $updateData, 'id = :id', ['id' => $userId]);
                        $message = 'User updated successfully.';
                        
                        // If password was changed for own account, suggest re-login
                        if ($userId == $currentUserId && !empty($password)) {
                            $message .= ' Password changed successfully. You may need to log in again.';
                        }
                    }
                }
            } else {
                // Check if username/email already exists
                $existing = db()->fetchOne(
                    "SELECT id FROM admin_users WHERE username = :username OR email = :email",
                    ['username' => $username, 'email' => $email]
                );
                
                if ($existing) {
                    $error = 'Username or email already exists.';
                } else {
                    if (empty($password)) {
                        $error = 'Password is required for new users.';
                    } else {
                        $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
                        db()->insert('admin_users', $updateData);
                        $message = 'User created successfully.';
                        header('Location: ' . url('admin/users.php'));
                        exit;
                    }
                }
            }
        } catch (\Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get all roles
$roles = $roleModel->getAll();

$pageTitle = $isEdit ? 'Edit User' : 'Create User';
include __DIR__ . '/includes/header.php';
?>

<div class="p-6">
    <div class="mb-6">
        <a href="<?= url('admin/users.php') ?>" class="text-blue-600 hover:underline">
            <i class="fas fa-arrow-left mr-2"></i> Back to Users
        </a>
        <h1 class="text-3xl font-bold mt-2"><?= $isEdit ? 'Edit User' : 'Create User' ?></h1>
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
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" class="space-y-6">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium mb-2">Username *</label>
                    <input type="text" name="username" value="<?= escape($user['username'] ?? '') ?>" 
                           required
                           class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Email *</label>
                    <input type="email" name="email" value="<?= escape($user['email'] ?? '') ?>" 
                           required
                           class="w-full px-4 py-2 border rounded-lg">
                </div>
            </div>
            
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium mb-2">Full Name</label>
                    <input type="text" name="name" value="<?= escape($user['name'] ?? '') ?>" 
                           class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Role</label>
                    <select name="role_id" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">No Role</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>" 
                                    <?= ($user['role_id'] ?? '') == $role['id'] ? 'selected' : '' ?>>
                                <?= escape($role['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">
                    Password <?= $isEdit ? '(leave blank to keep current)' : '*' ?>
                </label>
                <input type="password" name="password" 
                       <?= !$isEdit ? 'required' : '' ?>
                       class="w-full px-4 py-2 border rounded-lg"
                       placeholder="<?= $isEdit ? 'Leave blank to keep current password' : 'Enter password' ?>">
                <?php if ($isEdit): ?>
                    <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password</p>
                <?php endif; ?>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" 
                           <?= ($user['is_active'] ?? 1) ? 'checked' : '' ?>
                           <?= ($isEdit && $userId == $currentUserId) ? 'disabled' : '' ?>
                           class="mr-2">
                    <span>Active</span>
                    <?php if ($isEdit && $userId == $currentUserId): ?>
                        <span class="text-xs text-gray-500 ml-2">(You cannot deactivate your own account)</span>
                    <?php endif; ?>
                </label>
            </div>
            
            <div class="flex gap-2 pt-4 border-t">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i> Save User
                </button>
                <a href="<?= url('admin/users.php') ?>" class="btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

