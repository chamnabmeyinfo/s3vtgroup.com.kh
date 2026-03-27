<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Category;

$categoryModel = new Category();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'save_selections') {
            // Get selected categories
            $selectedCategories = $_POST['selected_categories'] ?? [];
            $useSelected = !empty($_POST['use_selected_categories']) ? 1 : 0;
            $autoCategories = !empty($_POST['auto_categories']) ? 1 : 0;
            
            // Update settings
            try {
                $db = \App\Database\Connection::getInstance();
                
                // Save auto categories setting
                $db->query(
                    "INSERT INTO settings (`key`, `value`) VALUES ('products_menu_auto_categories', :value1)
                     ON DUPLICATE KEY UPDATE `value` = :value2",
                    ['value1' => $autoCategories, 'value2' => $autoCategories]
                );
                
                // Check if table exists
                try {
                    $db->fetchOne("SELECT 1 FROM menu_category_selections LIMIT 1");
                } catch (\Exception $e) {
                    $error = 'Database table does not exist. Please run the migration: database/run sql phpmyadmin/menu-categories-selection.sql';
                    // Still save the setting even if table doesn't exist
                    $db->query(
                        "INSERT INTO settings (`key`, `value`) VALUES ('products_menu_use_selected_categories', :value1)
                         ON DUPLICATE KEY UPDATE `value` = :value2",
                        ['value1' => $useSelected, 'value2' => $useSelected]
                    );
                }
                
                if (empty($error)) {
                    // Update setting
                    $db->query(
                        "INSERT INTO settings (`key`, `value`) VALUES ('products_menu_use_selected_categories', :value1)
                         ON DUPLICATE KEY UPDATE `value` = :value2",
                        ['value1' => $useSelected, 'value2' => $useSelected]
                    );
                    
                    // Clear existing selections
                    $db->query("DELETE FROM menu_category_selections WHERE menu_item_id IS NULL", []);
                    
                    // Insert new selections
                    if ($useSelected && !empty($selectedCategories)) {
                        foreach ($selectedCategories as $index => $categoryId) {
                            $categoryId = (int)$categoryId;
                            if ($categoryId > 0) {
                                try {
                                    $db->insert('menu_category_selections', [
                                        'menu_item_id' => null, // NULL means it's for the default Products menu
                                        'category_id' => $categoryId,
                                        'display_order' => $index,
                                        'is_active' => 1
                                    ]);
                                } catch (\Exception $e) {
                                    // Skip duplicates
                                }
                            }
                        }
                    }
                    
                    $message = 'Category selections saved successfully!';
                }
            } catch (\Exception $e) {
                $error = 'Error saving selections: ' . $e->getMessage();
            }
        }
    }
}

// Get current setting
$useSelected = false;
$tableExists = false;
$selectedCategoryIds = [];

try {
    $db = \App\Database\Connection::getInstance();
    
    // Check if table exists
    try {
        $db->fetchOne("SELECT 1 FROM menu_category_selections LIMIT 1");
        $tableExists = true;
    } catch (\Exception $e) {
        $tableExists = false;
        if (empty($error)) {
            $error = 'Please run the database migration first: database/run sql phpmyadmin/menu-categories-selection.sql';
        }
    }
    
    if ($tableExists) {
        $setting = $db->fetchOne(
            "SELECT value FROM settings WHERE `key` = 'products_menu_use_selected_categories'"
        );
        $useSelected = !empty($setting) && $setting['value'] == '1';
        
        // Get selected categories
        if ($useSelected) {
            try {
                $selected = $db->fetchAll(
                    "SELECT category_id FROM menu_category_selections 
                     WHERE menu_item_id IS NULL AND is_active = 1 
                     ORDER BY display_order ASC"
                );
                $selectedCategoryIds = array_column($selected, 'category_id');
            } catch (\Exception $e) {
                $selectedCategoryIds = [];
            }
        }
    }
} catch (\Exception $e) {
    $useSelected = false;
    $selectedCategoryIds = [];
    if (empty($error)) {
        $error = 'Error loading settings: ' . $e->getMessage();
    }
}

// Get all categories
$allCategories = $categoryModel->getAll(true);

include __DIR__ . '/includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-list-ul text-blue-600 mr-3"></i>Products Menu Categories
                </h1>
                <p class="text-gray-600 mt-2">
                    Select which categories appear in the Products menu dropdown
                </p>
            </div>
            <a href="menus.php" class="btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back to Menus
            </a>
        </div>
        
        <?php if (!empty($message)): ?>
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i><?= escape($message) ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i><?= escape($error) ?>
        </div>
        <?php endif; ?>
        
        <?php if (!$tableExists): ?>
        <div class="mb-4 p-4 bg-yellow-100 border border-yellow-400 text-yellow-800 rounded-lg">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mr-3 mt-1"></i>
                <div>
                    <h3 class="font-semibold mb-2">Database Migration Required</h3>
                    <p class="text-sm mb-2">The menu category selection feature requires a database table. Please run the following SQL migration:</p>
                    <code class="block bg-yellow-50 p-2 rounded text-xs mb-2">database/run sql phpmyadmin/menu-categories-selection.sql</code>
                    <p class="text-sm">After running the migration, refresh this page to use the category selection feature.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <form method="POST" id="categoriesForm">
            <input type="hidden" name="action" value="save_selections">
            
            <!-- Toggles -->
            <div class="mb-6 space-y-4">
                <!-- Auto Inject Categories Toggle -->
                <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">
                                <i class="fas fa-magic text-green-600 mr-2"></i>Auto-Show Categories as Sub-Menu
                            </label>
                            <p class="text-xs text-gray-600">
                                When enabled, all categories will automatically appear as sub-menu items under "Products" in the menu. No need to manually add them.
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <?php
                            $autoInject = true;
                            try {
                                $autoSetting = $db->fetchOne(
                                    "SELECT value FROM settings WHERE `key` = 'products_menu_auto_categories'"
                                );
                                $autoInject = empty($autoSetting) || $autoSetting['value'] == '1';
                            } catch (\Exception $e) {
                                $autoInject = true;
                            }
                            ?>
                            <input type="checkbox" name="auto_categories" value="1" 
                                   <?= $autoInject ? 'checked' : '' ?>
                                   class="sr-only peer">
                            <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-green-600"></div>
                        </label>
                    </div>
                </div>
                
                <!-- Use Selected Categories Toggle -->
                <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">
                                <i class="fas fa-toggle-on text-blue-600 mr-2"></i>Use Selected Categories
                            </label>
                            <p class="text-xs text-gray-600">
                                When enabled, only selected categories will appear in the Products menu. When disabled, all active categories will be shown. (Only works if Auto-Show is enabled)
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="use_selected_categories" value="1" 
                                   <?= $useSelected ? 'checked' : '' ?>
                                   onchange="toggleCategorySelection()"
                                   class="sr-only peer">
                            <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Category Selection -->
            <div id="categorySelection" class="<?= $useSelected ? '' : 'hidden' ?>">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-folder-open text-indigo-600 mr-2"></i>Select Categories
                    </label>
                    <p class="text-xs text-gray-600 mb-4">
                        Choose which categories should appear in the Products menu dropdown. You can drag to reorder them.
                    </p>
                    
                    <div class="border-2 border-gray-300 rounded-lg p-4 max-h-96 overflow-y-auto bg-gray-50">
                        <div id="categoryList" class="space-y-2">
                            <?php 
                            // Show selected categories first, then unselected
                            $selectedCats = [];
                            $unselectedCats = [];
                            
                            foreach ($allCategories as $cat) {
                                if (in_array($cat['id'], $selectedCategoryIds)) {
                                    $selectedCats[] = $cat;
                                } else {
                                    $unselectedCats[] = $cat;
                                }
                            }
                            
                            // Sort selected by display order
                            usort($selectedCats, function($a, $b) use ($selectedCategoryIds) {
                                $posA = array_search($a['id'], $selectedCategoryIds);
                                $posB = array_search($b['id'], $selectedCategoryIds);
                                return $posA <=> $posB;
                            });
                            
                            // Display selected categories
                            foreach ($selectedCats as $cat):
                                $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $cat['level'] ?? 0);
                                $prefix = ($cat['level'] ?? 0) > 0 ? '└─ ' : '';
                            ?>
                            <label class="flex items-center p-3 bg-white rounded-lg border-2 border-blue-300 hover:border-blue-500 cursor-pointer transition-all category-item" data-category-id="<?= $cat['id'] ?>">
                                <input type="checkbox" name="selected_categories[]" value="<?= $cat['id'] ?>" checked class="mr-3 w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                                <span class="flex-1 text-sm font-medium text-gray-800">
                                    <?= $indent . $prefix . escape($cat['name']) ?>
                                </span>
                                <i class="fas fa-grip-vertical text-gray-400 cursor-move handle"></i>
                            </label>
                            <?php endforeach; ?>
                            
                            <!-- Unselected categories -->
                            <?php foreach ($unselectedCats as $cat):
                                $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $cat['level'] ?? 0);
                                $prefix = ($cat['level'] ?? 0) > 0 ? '└─ ' : '';
                            ?>
                            <label class="flex items-center p-3 bg-white rounded-lg border-2 border-gray-200 hover:border-gray-400 cursor-pointer transition-all category-item" data-category-id="<?= $cat['id'] ?>">
                                <input type="checkbox" name="selected_categories[]" value="<?= $cat['id'] ?>" class="mr-3 w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                                <span class="flex-1 text-sm font-medium text-gray-800">
                                    <?= $indent . $prefix . escape($cat['name']) ?>
                                </span>
                                <i class="fas fa-grip-vertical text-gray-400 cursor-move handle hidden"></i>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Info when disabled -->
            <div id="allCategoriesInfo" class="<?= $useSelected ? 'hidden' : '' ?> mb-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-yellow-600 text-xl mr-3 mt-1"></i>
                    <div>
                        <h3 class="font-semibold text-yellow-800 mb-1">Showing All Categories</h3>
                        <p class="text-sm text-yellow-700">
                            When "Use Selected Categories" is disabled, all active categories will automatically appear as sub-menu items under Products. Enable the toggle above to manually select which categories to show.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Auto-Show Info -->
            <div class="mb-6 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                <div class="flex items-start">
                    <i class="fas fa-lightbulb text-indigo-600 text-xl mr-3 mt-1"></i>
                    <div>
                        <h3 class="font-semibold text-indigo-800 mb-1">How It Works</h3>
                        <p class="text-sm text-indigo-700 mb-2">
                            When "Auto-Show Categories as Sub-Menu" is enabled, all categories will automatically appear as sub-menu items under the "Products" menu item on the frontend. You don't need to manually add each category as a menu item.
                        </p>
                        <p class="text-sm text-indigo-700">
                            <strong>Tip:</strong> You can also use the "Sync Categories to Products" button in the menu editor to manually add categories as menu items in the database.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t">
                <a href="menus.php" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>Save Selections
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
function toggleCategorySelection() {
    const enabled = document.querySelector('input[name="use_selected_categories"]').checked;
    const selectionDiv = document.getElementById('categorySelection');
    const infoDiv = document.getElementById('allCategoriesInfo');
    
    if (enabled) {
        selectionDiv.classList.remove('hidden');
        infoDiv.classList.add('hidden');
    } else {
        selectionDiv.classList.add('hidden');
        infoDiv.classList.remove('hidden');
    }
}

// Make category list sortable
const categoryList = document.getElementById('categoryList');
if (categoryList) {
    new Sortable(categoryList, {
        handle: '.handle',
        animation: 150,
        onEnd: function(evt) {
            // Update order by moving checked items
            const items = Array.from(categoryList.querySelectorAll('.category-item'));
            items.forEach((item, index) => {
                const checkbox = item.querySelector('input[type="checkbox"]');
                if (checkbox && checkbox.checked) {
                    // Show handle for checked items
                    const handle = item.querySelector('.handle');
                    if (handle) handle.classList.remove('hidden');
                } else {
                    // Hide handle for unchecked items
                    const handle = item.querySelector('.handle');
                    if (handle) handle.classList.add('hidden');
                }
            });
        }
    });
}

// Show/hide handles based on checkbox state
document.querySelectorAll('input[name="selected_categories[]"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const item = this.closest('.category-item');
        const handle = item.querySelector('.handle');
        if (this.checked) {
            handle.classList.remove('hidden');
            item.classList.add('border-blue-300');
            item.classList.remove('border-gray-200');
        } else {
            handle.classList.add('hidden');
            item.classList.remove('border-blue-300');
            item.classList.add('border-gray-200');
        }
    });
});

// Initialize handle visibility
document.querySelectorAll('.category-item').forEach(item => {
    const checkbox = item.querySelector('input[type="checkbox"]');
    const handle = item.querySelector('.handle');
    if (checkbox && !checkbox.checked && handle) {
        handle.classList.add('hidden');
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
