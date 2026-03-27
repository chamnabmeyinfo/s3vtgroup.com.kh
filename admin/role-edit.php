<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Role;
use App\Models\Permission;

$roleId = $_GET['id'] ?? null;
$isEdit = !empty($roleId);

if ($isEdit) {
    requirePermission('edit_roles');
} else {
    requirePermission('create_roles');
}

$roleModel = new Role();
$permissionModel = new Permission();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $permissionIds = $_POST['permissions'] ?? [];
    
    if (empty($name)) {
        $error = 'Role name is required.';
    } else {
        try {
            if ($isEdit) {
                $roleModel->update($roleId, [
                    'name' => $name,
                    'description' => $description,
                    'is_active' => $isActive
                ]);
                $roleModel->assignPermissions($roleId, $permissionIds);
                $message = 'Role updated successfully.';
            } else {
                $newRoleId = $roleModel->create([
                    'name' => $name,
                    'description' => $description,
                    'is_active' => $isActive
                ]);
                $roleModel->assignPermissions($newRoleId, $permissionIds);
                $message = 'Role created successfully.';
                header('Location: ' . url('admin/roles.php'));
                exit;
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get role data
$role = null;
$rolePermissions = [];

if ($isEdit) {
    $role = $roleModel->getById($roleId);
    if (!$role) {
        $error = 'Role not found.';
    } else {
        $rolePermissions = array_column($role['permissions'], 'id');
    }
}

// Get all permissions grouped by category
$permissionsByCategory = $permissionModel->getAll(true);

$pageTitle = $isEdit ? 'Edit Role' : 'Create Role';
include __DIR__ . '/includes/header.php';
?>

<div class="p-6">
    <div class="mb-6">
        <a href="<?= url('admin/roles.php') ?>" class="text-blue-600 hover:underline">
            <i class="fas fa-arrow-left mr-2"></i> Back to Roles
        </a>
        <h1 class="text-3xl font-bold mt-2"><?= $isEdit ? 'Edit Role' : 'Create Role' ?></h1>
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
                    <label class="block text-sm font-medium mb-2">Role Name *</label>
                    <input type="text" name="name" value="<?= escape($role['name'] ?? '') ?>" 
                           required
                           class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Status</label>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" 
                               <?= ($role['is_active'] ?? 1) ? 'checked' : '' ?>
                               class="mr-2">
                        <span>Active</span>
                    </label>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Description</label>
                <textarea name="description" rows="3" 
                          class="w-full px-4 py-2 border rounded-lg"><?= escape($role['description'] ?? '') ?></textarea>
            </div>
            
            <?php if ($isEdit && ($role['is_system'] ?? false)): ?>
                <div class="bg-blue-50 border border-blue-200 rounded p-4">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        This is a system role. Some fields may be protected.
                    </p>
                </div>
            <?php endif; ?>
            
            <div>
                <label class="block text-sm font-medium mb-4">Permissions</label>
                <div class="space-y-4 max-h-96 overflow-y-auto border rounded-lg p-4">
                    <?php foreach ($permissionsByCategory as $category => $permissions): ?>
                        <div class="mb-4">
                            <h3 class="font-semibold text-gray-700 mb-2 capitalize">
                                <i class="fas fa-folder mr-2"></i><?= escape($category) ?>
                            </h3>
                            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-2 ml-6">
                                <?php foreach ($permissions as $permission): ?>
                                    <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded">
                                        <input type="checkbox" name="permissions[]" 
                                               value="<?= $permission['id'] ?>"
                                               <?= in_array($permission['id'], $rolePermissions) ? 'checked' : '' ?>
                                               class="mr-2">
                                        <div>
                                            <div class="text-sm font-medium"><?= escape($permission['name']) ?></div>
                                            <?php if ($permission['description']): ?>
                                                <div class="text-xs text-gray-500"><?= escape($permission['description']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-2 flex gap-2">
                    <button type="button" onclick="selectAllPermissions()" class="text-sm text-blue-600 hover:underline">
                        Select All
                    </button>
                    <button type="button" onclick="deselectAllPermissions()" class="text-sm text-blue-600 hover:underline">
                        Deselect All
                    </button>
                </div>
            </div>
            
            <div class="flex gap-2 pt-4 border-t">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i> Save Role
                </button>
                <a href="<?= url('admin/roles.php') ?>" class="btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function selectAllPermissions() {
    document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = true);
}

function deselectAllPermissions() {
    document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = false);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

