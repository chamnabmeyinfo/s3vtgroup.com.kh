<?php
/**
 * Enhanced Menu Editor
 * WordPress-like menu editor with post type support
 * 
 * This is an enhanced version that can replace or work alongside menu-edit.php
 */

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Menu;
use App\Models\MenuItem;
use App\Registry\PostTypeRegistry;

$menuModel = new Menu();
$itemModel = new MenuItem();
$message = '';
$error = '';
$menu = null;
$menuId = !empty($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = false;

// Get registered post types
$postTypes = PostTypeRegistry::getPostTypes();
$taxonomies = PostTypeRegistry::getTaxonomies();

// Handle AJAX: Get items for a post type
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false && !empty($input)) {
        $data = json_decode($input, true);
        header('Content-Type: application/json');
        
        if ($data['action'] === 'get_post_type_items') {
            $postType = $data['post_type'] ?? '';
            $items = PostTypeRegistry::getPostTypeItems($postType, ['active_only' => true]);
            echo json_encode(['success' => true, 'items' => $items]);
            exit;
        }
        
        if ($data['action'] === 'get_taxonomy_items') {
            $taxonomy = $data['taxonomy'] ?? '';
            $items = PostTypeRegistry::getTaxonomyItems($taxonomy, ['active_only' => true]);
            echo json_encode(['success' => true, 'items' => $items]);
            exit;
        }
        
        if ($data['action'] === 'add_items_to_menu') {
            $menuId = (int)($data['menu_id'] ?? 0);
            $items = $data['items'] ?? [];
            $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
            
            $added = 0;
            $errors = [];
            
            foreach ($items as $itemData) {
                $type = $itemData['type'] ?? 'custom';
                $objectId = !empty($itemData['object_id']) ? (int)$itemData['object_id'] : null;
                $title = $itemData['title'] ?? '';
                $url = $itemData['url'] ?? '#';
                
                $menuItemData = [
                    'menu_id' => $menuId,
                    'parent_id' => $parentId,
                    'type' => $type,
                    'object_id' => $objectId,
                    'title' => $title,
                    'url' => $url,
                    'target' => '_self',
                    'is_active' => 1
                ];
                
                // Set post_type if applicable
                if (in_array($type, ['product', 'page', 'service'])) {
                    $menuItemData['post_type'] = $type;
                }
                
                if ($itemModel->create($menuItemData)) {
                    $added++;
                } else {
                    $errors[] = "Failed to add: {$title}";
                }
            }
            
            echo json_encode([
                'success' => $added > 0,
                'added' => $added,
                'errors' => $errors
            ]);
            exit;
        }
    }
}

// Get menu data
if ($menuId > 0) {
    $menu = $menuModel->getById($menuId);
    if ($menu) {
        $isEdit = true;
        $items = $itemModel->getByMenuId($menuId);
    } else {
        $error = 'Menu not found';
        $menuId = 0;
    }
} else {
    $items = [];
}

$pageTitle = $isEdit ? 'Edit Menu' : 'Create Menu';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full p-4 md:p-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-xl p-4 md:p-6 mb-6 text-white">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-2">
                    <i class="fas fa-bars mr-2"></i><?= $isEdit ? 'Edit Menu' : 'Create Menu' ?>
                </h1>
                <p class="text-blue-100"><?= $isEdit ? 'Manage menu items and structure' : 'Create a new navigation menu' ?></p>
            </div>
            <a href="<?= url('admin/menus.php') ?>" class="bg-white/20 text-white px-4 py-2 rounded-lg font-semibold hover:bg-white/30 transition-all">
                <i class="fas fa-arrow-left mr-2"></i>Back to Menus
            </a>
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

    <?php if ($isEdit): ?>
    <div class="grid lg:grid-cols-4 gap-6">
        <!-- Left Panel: Add Items (WordPress-like) -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-lg p-6 sticky top-4">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-plus-circle text-blue-600 mr-2"></i>Add Items
                </h3>
                
                <!-- Tabs for Post Types -->
                <div class="mb-4">
                    <div class="flex border-b border-gray-200">
                        <button onclick="showAddItemsTab('post_types')" class="add-items-tab active px-4 py-2 text-sm font-semibold text-blue-600 border-b-2 border-blue-600">
                            Content
                        </button>
                        <button onclick="showAddItemsTab('custom')" class="add-items-tab px-4 py-2 text-sm font-semibold text-gray-600 hover:text-blue-600">
                            Custom
                        </button>
                    </div>
                </div>
                
                <!-- Post Types Tab -->
                <div id="postTypesTab" class="add-items-content">
                    <div class="space-y-4 max-h-[600px] overflow-y-auto">
                        <?php foreach ($postTypes as $postType => $postTypeData): ?>
                        <div class="border border-gray-200 rounded-lg p-3">
                            <button onclick="togglePostType('<?= escape($postType) ?>')" 
                                    class="w-full flex items-center justify-between text-left font-semibold text-gray-800 hover:text-blue-600">
                                <span>
                                    <i class="fas <?= escape($postTypeData['menu_icon'] ?? 'fa-file') ?> mr-2"></i>
                                    <?= escape($postTypeData['labels']['name'] ?? ucfirst($postType)) ?>
                                </span>
                                <i class="fas fa-chevron-down text-xs transform transition-transform" id="icon-<?= escape($postType) ?>"></i>
                            </button>
                            <div id="items-<?= escape($postType) ?>" class="hidden mt-2 space-y-2">
                                <div class="text-sm text-gray-600 mb-2">Loading...</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Taxonomies -->
                        <?php foreach ($taxonomies as $taxonomy => $taxonomyData): ?>
                        <div class="border border-gray-200 rounded-lg p-3">
                            <button onclick="toggleTaxonomy('<?= escape($taxonomy) ?>')" 
                                    class="w-full flex items-center justify-between text-left font-semibold text-gray-800 hover:text-blue-600">
                                <span>
                                    <i class="fas fa-tags mr-2"></i>
                                    <?= escape($taxonomyData['labels']['name'] ?? ucfirst($taxonomy)) ?>
                                </span>
                                <i class="fas fa-chevron-down text-xs transform transition-transform" id="tax-icon-<?= escape($taxonomy) ?>"></i>
                            </button>
                            <div id="tax-items-<?= escape($taxonomy) ?>" class="hidden mt-2 space-y-2">
                                <div class="text-sm text-gray-600 mb-2">Loading...</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Custom Link Tab -->
                <div id="customTab" class="add-items-content hidden">
                    <form id="customLinkForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Link Text</label>
                            <input type="text" id="customLinkTitle" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">URL</label>
                            <input type="url" id="customLinkUrl" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                        </div>
                        <button type="button" onclick="addCustomLink()" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i>Add to Menu
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Right Panel: Menu Structure -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Menu Structure</h2>
                    <button onclick="saveMenuOrder()" class="bg-green-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-700">
                        <i class="fas fa-save mr-2"></i>Save Menu
                    </button>
                </div>
                
                <div id="menuItemsList" class="space-y-2 min-h-[400px]">
                    <?php if (empty($items)): ?>
                        <p class="text-gray-500 text-center py-8">No menu items. Add items from the left panel.</p>
                    <?php else: ?>
                        <?php
                        function renderMenuItems($items, $parentId = null) {
                            $children = array_filter($items, function($item) use ($parentId) {
                                $itemParentId = $item['parent_id'] ?? null;
                                return ($itemParentId === null && $parentId === null) || 
                                       ($itemParentId !== null && (int)$itemParentId === (int)$parentId);
                            });
                            
                            if (empty($children)) return '';
                            
                            $html = '<ul class="space-y-2">';
                            foreach ($children as $item) {
                                $hasChildren = !empty(array_filter($items, function($i) use ($item) {
                                    return ($i['parent_id'] ?? null) == $item['id'];
                                }));
                                
                                $html .= '<li class="menu-item bg-gray-50 rounded-lg p-3 border border-gray-200" data-item-id="' . $item['id'] . '">';
                                $html .= '<div class="flex items-center justify-between">';
                                $html .= '<div class="flex items-center gap-3 flex-1">';
                                $html .= '<i class="fas fa-grip-vertical text-gray-400 cursor-move"></i>';
                                if ($item['icon']): 
                                    $html .= '<i class="' . escape($item['icon']) . ' text-blue-600"></i>';
                                endif;
                                $html .= '<span class="font-semibold text-gray-800">' . escape($item['title']) . '</span>';
                                $html .= '<span class="text-xs text-gray-500">(' . escape($item['type']) . ')</span>';
                                $html .= '</div>';
                                $html .= '<div class="flex gap-2">';
                                $html .= '<button onclick="editMenuItem(' . $item['id'] . ')" class="text-blue-600 hover:text-blue-800"><i class="fas fa-edit"></i></button>';
                                $html .= '<button onclick="deleteMenuItem(' . $item['id'] . ')" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>';
                                $html .= '</div>';
                                $html .= '</div>';
                                
                                if ($hasChildren) {
                                    $html .= renderMenuItems($items, $item['id']);
                                }
                                
                                $html .= '</li>';
                            }
                            $html .= '</ul>';
                            return $html;
                        }
                        echo renderMenuItems($items);
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
        <!-- Create Menu Form -->
        <form method="POST" class="bg-white rounded-xl shadow-lg p-6">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Menu Name *</label>
                    <input type="text" name="menu_name" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Menu Slug</label>
                    <input type="text" name="menu_slug" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg">
                </div>
            </div>
            <div class="mt-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea name="menu_description" rows="2" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Create Menu
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
const menuId = <?= $menuId ?>;

// Initialize drag and drop
document.addEventListener('DOMContentLoaded', function() {
    const list = document.getElementById('menuItemsList');
    if (list && typeof Sortable !== 'undefined') {
        new Sortable(list, {
            handle: '.fa-grip-vertical',
            animation: 150,
            ghostClass: 'sortable-ghost',
            group: 'menu-items',
            draggable: '.menu-item',
        });
    }
});

function showAddItemsTab(tab) {
    document.querySelectorAll('.add-items-tab').forEach(t => {
        t.classList.remove('active', 'border-blue-600', 'text-blue-600');
        t.classList.add('text-gray-600');
    });
    document.querySelectorAll('.add-items-content').forEach(c => c.classList.add('hidden'));
    
    event.target.classList.add('active', 'border-blue-600', 'text-blue-600');
    event.target.classList.remove('text-gray-600');
    
    document.getElementById(tab + 'Tab').classList.remove('hidden');
}

async function togglePostType(postType) {
    const container = document.getElementById('items-' + postType);
    const icon = document.getElementById('icon-' + postType);
    
    if (container.classList.contains('hidden')) {
        container.classList.remove('hidden');
        icon.classList.add('rotate-180');
        
        // Load items
        try {
            const response = await fetch('<?= url("admin/menu-edit-enhanced.php?id={$menuId}") ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'get_post_type_items',
                    post_type: postType
                })
            });
            const data = await response.json();
            
            if (data.success && data.items) {
                let html = '<div class="space-y-2">';
                data.items.forEach(item => {
                    html += `<div class="flex items-center justify-between p-2 bg-gray-50 rounded hover:bg-gray-100">
                        <span class="text-sm">${item.name || item.title}</span>
                        <button onclick="addItemToMenu('${postType}', ${item.id}, '${(item.name || item.title).replace(/'/g, "\\'")}', '${(item.url || item.slug || '#').replace(/'/g, "\\'")}')" 
                                class="text-blue-600 hover:text-blue-800 text-xs">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>`;
                });
                html += '</div>';
                container.innerHTML = html;
            }
        } catch (error) {
            console.error('Error loading items:', error);
        }
    } else {
        container.classList.add('hidden');
        icon.classList.remove('rotate-180');
    }
}

async function toggleTaxonomy(taxonomy) {
    const container = document.getElementById('tax-items-' + taxonomy);
    const icon = document.getElementById('tax-icon-' + taxonomy);
    
    if (container.classList.contains('hidden')) {
        container.classList.remove('hidden');
        icon.classList.add('rotate-180');
        
        // Load taxonomy items
        try {
            const response = await fetch('<?= url("admin/menu-edit-enhanced.php?id={$menuId}") ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'get_taxonomy_items',
                    taxonomy: taxonomy
                })
            });
            const data = await response.json();
            
            if (data.success && data.items) {
                let html = '<div class="space-y-2">';
                data.items.forEach(item => {
                    html += `<div class="flex items-center justify-between p-2 bg-gray-50 rounded hover:bg-gray-100">
                        <span class="text-sm">${item.name}</span>
                        <button onclick="addItemToMenu('category', ${item.id}, '${item.name.replace(/'/g, "\\'")}', 'products.php?category=${item.slug}')" 
                                class="text-blue-600 hover:text-blue-800 text-xs">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>`;
                });
                html += '</div>';
                container.innerHTML = html;
            }
        } catch (error) {
            console.error('Error loading taxonomy items:', error);
        }
    } else {
        container.classList.add('hidden');
        icon.classList.remove('rotate-180');
    }
}

async function addItemToMenu(type, objectId, title, url) {
    if (!menuId || menuId <= 0) {
        alert('Please save the menu first');
        return;
    }
    
    try {
        const response = await fetch('<?= url("admin/menu-edit-enhanced.php?id={$menuId}") ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'add_items_to_menu',
                menu_id: menuId,
                items: [{
                    type: type,
                    object_id: objectId,
                    title: title,
                    url: url
                }]
            })
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert('Error adding item');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error adding item');
    }
}

function addCustomLink() {
    const title = document.getElementById('customLinkTitle').value;
    const url = document.getElementById('customLinkUrl').value;
    
    if (!title || !url) {
        alert('Please fill in both fields');
        return;
    }
    
    addItemToMenu('custom', null, title, url);
    document.getElementById('customLinkForm').reset();
}

function saveMenuOrder() {
    // Implementation similar to menu-edit.php
    alert('Save order functionality - to be implemented');
}

function editMenuItem(id) {
    // Redirect to edit or open modal
    window.location.href = '<?= url("admin/menu-edit.php?id={$menuId}") ?>&edit_item=' + id;
}

function deleteMenuItem(id) {
    if (confirm('Delete this menu item?')) {
        // Implement delete
        alert('Delete functionality - to be implemented');
    }
}
</script>

<style>
.sortable-ghost {
    opacity: 0.4;
}
.menu-item {
    cursor: move;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
