<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Role;

requirePermission('view_roles');

$roleModel = new Role();
$message = '';
$error = '';

// Handle delete
if (!empty($_GET['delete']) && hasPermission('delete_roles')) {
    try {
        $roleModel->delete($_GET['delete']);
        $message = 'Role deleted successfully.';
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }
}

// Get all roles
$roles = $roleModel->getAll(true);

$pageTitle = 'Roles';
include __DIR__ . '/includes/header.php';
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Roles</h1>
        <?php if (hasPermission('create_roles')): ?>
        <a href="<?= url('admin/role-edit.php') ?>" class="btn-primary">
            <i class="fas fa-plus mr-2"></i> Add New Role
        </a>
        <?php endif; ?>
    </div>
    
    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?= escape($message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= escape($error) ?>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Permissions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Users</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($roles as $role): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?= escape($role['name']) ?></div>
                        <?php if ($role['is_system']): ?>
                            <span class="text-xs text-blue-600">System Role</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <code class="text-xs"><?= escape($role['slug']) ?></code>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <?= escape($role['description'] ?? '-') ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">
                            <?= number_format($role['permission_count'] ?? 0) ?> permission(s)
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded">
                            <?= number_format($role['user_count'] ?? 0) ?> user(s)
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded <?= $role['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                            <?= $role['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <a href="<?= url('admin/role-edit.php?id=' . $role['id']) ?>" 
                           class="text-blue-600 hover:text-blue-900" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php if (!$role['is_system'] && hasPermission('delete_roles')): ?>
                        <a href="?delete=<?= $role['id'] ?>" 
                           onclick="return confirm('Are you sure you want to delete this role?')" 
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

