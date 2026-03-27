<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuLocation;

$menuModel = new Menu();
$itemModel = new MenuItem();
$locationModel = new MenuLocation();
$message = '';
$error = '';

// Handle delete
if (!empty($_GET['delete'])) {
    try {
        $id = (int)$_GET['delete'];
        if ($id <= 0) {
            $error = 'Invalid menu ID.';
        } else {
            $deleted = $menuModel->delete($id);
            if ($deleted) {
                $message = 'Menu deleted successfully.';
            } else {
                $error = 'Menu not found or could not be deleted.';
            }
        }
    } catch (\Exception $e) {
        $error = 'Error deleting menu: ' . $e->getMessage();
    }
}

// Handle duplicate
if (!empty($_GET['duplicate'])) {
    try {
        $id = (int)$_GET['duplicate'];
        $newId = $menuModel->duplicate($id);
        if ($newId) {
            $message = 'Menu duplicated successfully.';
        } else {
            $error = 'Failed to duplicate menu.';
        }
    } catch (\Exception $e) {
        $error = 'Error duplicating menu: ' . $e->getMessage();
    }
}

// Get all menus
$menus = $menuModel->getAll();
$locations = $locationModel->getAll();
$locationMap = [];
foreach ($locations as $loc) {
    if ($loc['menu_id']) {
        $locationMap[$loc['menu_id']][] = $loc['location'];
    }
}

$pageTitle = 'Menus';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full p-4 md:p-6">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-xl p-4 md:p-6 mb-6 text-white">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-2">
                    <i class="fas fa-bars mr-2"></i>Menus
                </h1>
                <p class="text-blue-100">Manage navigation menus</p>
            </div>
            <div class="flex gap-3">
                <a href="<?= url('admin/menu-locations.php') ?>" class="bg-white/20 text-white px-4 py-2 rounded-lg font-semibold hover:bg-white/30 transition-all">
                    <i class="fas fa-map-marker-alt mr-2"></i>Menu Locations
                </a>
                <a href="<?= url('admin/menu-edit.php') ?>" class="bg-white text-blue-600 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition-all">
                    <i class="fas fa-plus mr-2"></i>Add New Menu
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
        <i class="fas fa-check-circle mr-2"></i><?= escape($message) ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6">
        <i class="fas fa-exclamation-circle mr-2"></i><?= escape($error) ?>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <?php if (empty($menus)): ?>
            <div class="p-8 md:p-12 text-center">
                <i class="fas fa-bars text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-xl font-bold text-gray-700 mb-2">No Menus</h3>
                <p class="text-gray-500 mb-6">Create your first menu to get started.</p>
                <a href="<?= url('admin/menu-edit.php') ?>" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700 transition-all">
                    <i class="fas fa-plus mr-2"></i>Add First Menu
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Slug</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Items</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Locations</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($menus as $menu): 
                            $itemCount = count($itemModel->getByMenuId($menu['id']));
                            $assignedLocations = $locationMap[$menu['id']] ?? [];
                        ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gray-900"><?= escape($menu['name']) ?></div>
                                    <?php if ($menu['description']): ?>
                                        <div class="text-sm text-gray-500"><?= escape($menu['description']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <code class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-sm"><?= escape($menu['slug']) ?></code>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                                        <?= $itemCount ?> item<?= $itemCount != 1 ? 's' : '' ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if (!empty($assignedLocations)): ?>
                                        <?php foreach ($assignedLocations as $loc): ?>
                                            <span class="inline-block px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold mr-1 mb-1">
                                                <?= escape($loc) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-2">
                                        <a href="<?= url("admin/menu-edit.php?id={$menu['id']}") ?>" 
                                           class="bg-blue-100 hover:bg-blue-200 text-blue-700 p-2 rounded-lg transition-all" 
                                           title="Edit">
                                            <i class="fas fa-edit text-sm"></i>
                                        </a>
                                        <a href="<?= url("admin/menu-edit.php?menu_id={$menu['id']}") ?>" 
                                           class="bg-indigo-100 hover:bg-indigo-200 text-indigo-700 p-2 rounded-lg transition-all" 
                                           title="Manage Items">
                                            <i class="fas fa-list text-sm"></i>
                                        </a>
                                        <a href="?duplicate=<?= $menu['id'] ?>" 
                                           class="bg-purple-100 hover:bg-purple-200 text-purple-700 p-2 rounded-lg transition-all" 
                                           title="Duplicate">
                                            <i class="fas fa-copy text-sm"></i>
                                        </a>
                                        <a href="?delete=<?= $menu['id'] ?>" 
                                           onclick="return confirm('Are you sure you want to delete this menu?')" 
                                           class="text-red-600 hover:text-red-800 p-2 rounded hover:bg-red-50 transition-all" 
                                           title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
