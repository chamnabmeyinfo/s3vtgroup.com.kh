<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Menu;
use App\Models\MenuLocation;

$menuModel = new Menu();
$locationModel = new MenuLocation();
$message = '';
$error = '';

// Handle create new location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'create_location') {
    try {
        $data = [
            'location' => trim($_POST['location'] ?? ''),
            'area' => trim($_POST['area'] ?? 'main'),
            'layout' => trim($_POST['layout'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'menu_id' => !empty($_POST['menu_id']) ? (int)$_POST['menu_id'] : null
        ];
        
        if (empty($data['location'])) {
            $error = 'Location name is required.';
        } else {
            // Sanitize location name (lowercase, no spaces, alphanumeric and underscores only)
            $data['location'] = strtolower(preg_replace('/[^a-z0-9_]/', '_', $data['location']));
            
            $result = $locationModel->create($data);
            if ($result) {
                $message = 'Menu location created successfully.';
            } else {
                $error = 'Failed to create location. It may already exist.';
            }
        }
    } catch (\Exception $e) {
        $error = 'Error creating location: ' . $e->getMessage();
    }
}

// Handle update location assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'update_assignments') {
    try {
        foreach ($_POST['locations'] as $locationId => $menuId) {
            $locationId = (int)$locationId;
            $menuId = !empty($menuId) ? (int)$menuId : null;
            
            $locationModel->update($locationId, ['menu_id' => $menuId]);
        }
        $message = 'Menu locations updated successfully.';
    } catch (\Exception $e) {
        $error = 'Error updating locations: ' . $e->getMessage();
    }
}

// Handle delete location
if (!empty($_GET['delete'])) {
    try {
        $locationId = (int)$_GET['delete'];
        $deleted = $locationModel->delete($locationId);
        if ($deleted) {
            $message = 'Location deleted successfully.';
        } else {
            $error = 'Failed to delete location.';
        }
    } catch (\Exception $e) {
        $error = 'Error deleting location: ' . $e->getMessage();
    }
}

$menus = $menuModel->getAll();
$locations = $locationModel->getAll();

// Check if area column exists
$hasAreaColumn = false;
try {
    db()->fetchOne("SELECT area FROM menu_locations LIMIT 1");
    $hasAreaColumn = true;
} catch (\Exception $e) {
    $hasAreaColumn = false;
}

// Group locations by area
$locationsByArea = [];
foreach ($locations as $loc) {
    $area = ($hasAreaColumn && isset($loc['area'])) ? $loc['area'] : 'main';
    if (!isset($locationsByArea[$area])) {
        $locationsByArea[$area] = [];
    }
    $locationsByArea[$area][] = $loc;
}

// Define available areas
$availableAreas = [
    'header' => 'Top Header',
    'main' => 'Main Navigation',
    'footer' => 'Footer',
    'sidebar' => 'Sidebar',
    'mobile' => 'Mobile Menu',
    'other' => 'Other'
];

$pageTitle = 'Menu Locations';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full p-4 md:p-6">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-xl p-4 md:p-6 mb-6 text-white">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-2">
                    <i class="fas fa-map-marker-alt mr-2"></i>Menu Locations
                </h1>
                <p class="text-blue-100">Create and assign menus to theme locations</p>
            </div>
            <div class="flex gap-3">
                <a href="<?= url('admin/menus.php') ?>" class="bg-white/20 text-white px-4 py-2 rounded-lg font-semibold hover:bg-white/30 transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Menus
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

    <?php if (!$hasAreaColumn): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg mb-6">
        <div class="flex items-start">
            <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 mt-1"></i>
            <div class="flex-1">
                <h3 class="font-semibold text-yellow-800 mb-2">Area Feature Not Available</h3>
                <p class="text-yellow-700 text-sm mb-4">The <code class="bg-yellow-100 px-2 py-1 rounded">area</code> column doesn't exist yet. Please import <code class="bg-yellow-100 px-2 py-1 rounded">database/add-menu-location-area.sql</code> to enable area categorization.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Create New Location Form -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-plus-circle text-blue-600 mr-2"></i>Create New Location
        </h2>
        <form method="POST" class="grid md:grid-cols-5 gap-4">
            <input type="hidden" name="action" value="create_location">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Location Name *</label>
                <input type="text" name="location" required 
                       placeholder="e.g., top_header, main_nav"
                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Lowercase, underscores only (auto-formatted)</p>
            </div>
            <?php if ($hasAreaColumn): ?>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Area *</label>
                <select name="area" required 
                        class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($availableAreas as $value => $label): ?>
                        <option value="<?= escape($value) ?>"><?= escape($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Layout/Theme</label>
                <input type="text" name="layout" 
                       placeholder="e.g., default, modern, classic"
                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Optional: Theme or layout identifier</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <input type="text" name="description" 
                       placeholder="Brief description"
                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-primary w-full">
                    <i class="fas fa-plus"></i>Create Location
                </button>
            </div>
        </form>
    </div>

    <!-- Locations by Area -->
    <form method="POST" id="locationsForm">
        <input type="hidden" name="action" value="update_assignments">
        
        <?php foreach ($availableAreas as $areaValue => $areaLabel): 
            $areaLocations = $locationsByArea[$areaValue] ?? [];
            if (empty($areaLocations) && $areaValue !== 'other') continue;
        ?>
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-<?= $areaValue === 'header' ? 'arrow-up' : ($areaValue === 'footer' ? 'arrow-down' : ($areaValue === 'sidebar' ? 'bars' : ($areaValue === 'mobile' ? 'mobile-alt' : 'ellipsis-h'))) ?> text-blue-600 mr-2"></i>
                <?= escape($areaLabel) ?>
            </h2>
            
            <?php if (empty($areaLocations)): ?>
                <p class="text-gray-500 text-sm italic">No locations in this area yet.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($areaLocations as $loc): ?>
                    <div class="border-b pb-4 last:border-b-0 last:pb-0">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-800">
                                        <?= escape(ucwords(str_replace('_', ' ', $loc['location']))) ?>
                                    </h3>
                                    <code class="text-xs bg-gray-100 px-2 py-1 rounded text-gray-600">
                                        <?= escape($loc['location']) ?>
                                    </code>
                                <a href="#" 
                                   onclick="deleteLocation(<?= $loc['id'] ?>); return false;"
                                   class="action-btn action-btn-delete" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                                </div>
                                <?php if (!empty($loc['description'])): ?>
                                <p class="text-sm text-gray-600"><?= escape($loc['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="md:w-64">
                                <select name="locations[<?= $loc['id'] ?>]" 
                                        class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">-- Select Menu --</option>
                                    <?php foreach ($menus as $menu): ?>
                                        <option value="<?= $menu['id'] ?>" 
                                                <?= ($loc['menu_id'] ?? null) == $menu['id'] ? 'selected' : '' ?>>
                                            <?= escape($menu['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
        <!-- Other locations (if any) -->
        <?php if (!empty($locationsByArea['other'])): ?>
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-ellipsis-h text-blue-600 mr-2"></i>Other Locations
            </h2>
            <div class="space-y-4">
                <?php foreach ($locationsByArea['other'] as $loc): ?>
                <div class="border-b pb-4 last:border-b-0 last:pb-0">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <?= escape(ucwords(str_replace('_', ' ', $loc['location']))) ?>
                                </h3>
                                <code class="text-xs bg-gray-100 px-2 py-1 rounded text-gray-600">
                                    <?= escape($loc['location']) ?>
                                </code>
                                    <a href="#" 
                                       onclick="deleteLocation(<?= $loc['id'] ?>); return false;"
                                       class="action-btn action-btn-delete" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                            </div>
                            <?php if (!empty($loc['description'])): ?>
                            <p class="text-sm text-gray-600"><?= escape($loc['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="md:w-64">
                            <select name="locations[<?= $loc['id'] ?>]" 
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Select Menu --</option>
                                <?php foreach ($menus as $menu): ?>
                                    <option value="<?= $menu['id'] ?>" 
                                            <?= ($loc['menu_id'] ?? null) == $menu['id'] ? 'selected' : '' ?>>
                                        <?= escape($menu['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="mt-6 flex justify-end">
            <button type="submit" class="btn-primary btn-lg">
                <i class="fas fa-save"></i>Save All Assignments
            </button>
        </div>
    </form>
</div>

<script>
async function deleteLocation(locationId) {
    const confirmed = await customConfirm('Are you sure you want to delete this location?', 'Delete Location');
    if (confirmed) {
        window.location.href = '?delete=' + locationId;
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
