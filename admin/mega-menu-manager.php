<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\MenuItem;
use App\Models\MegaMenuWidget;
use App\Models\Product;
use App\Models\Category;

$itemModel = new MenuItem();
$widgetModel = new MegaMenuWidget();
$productModel = new Product();
$categoryModel = new Category();

$message = '';
$error = '';
$menuItemId = !empty($_GET['menu_item_id']) ? (int)$_GET['menu_item_id'] : 0;
$menuItem = null;

if ($menuItemId > 0) {
    $menuItem = $itemModel->getById($menuItemId);
    if (!$menuItem) {
        $error = 'Menu item not found';
        $menuItemId = 0;
    } elseif (empty($menuItem['mega_menu_enabled'])) {
        $error = 'Mega menu is not enabled for this menu item. Please enable it in the menu editor first.';
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $menuItemId > 0) {
    if (!empty($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add_widget') {
            $widgetData = [
                'menu_item_id' => $menuItemId,
                'widget_type' => $_POST['widget_type'] ?? 'text',
                'widget_title' => $_POST['widget_title'] ?? '',
                'widget_content' => $_POST['widget_content'] ?? '',
                'widget_image' => $_POST['widget_image'] ?? '',
                'widget_url' => $_POST['widget_url'] ?? '',
                'widget_column' => (int)($_POST['widget_column'] ?? 1),
                'widget_order' => (int)($_POST['widget_order'] ?? 0),
                'widget_width' => $_POST['widget_width'] ?? 'full',
                'widget_style' => $_POST['widget_style'] ?? '',
                'is_active' => !empty($_POST['is_active']) ? 1 : 0
            ];
            
            if ($widgetModel->create($widgetData)) {
                $message = 'Widget added successfully!';
            } else {
                $error = 'Failed to add widget';
            }
        }
        
        elseif ($action === 'update_widget') {
            $widgetId = (int)($_POST['widget_id'] ?? 0);
            if ($widgetId > 0) {
                $widgetData = [
                    'widget_type' => $_POST['widget_type'] ?? 'text',
                    'widget_title' => $_POST['widget_title'] ?? '',
                    'widget_content' => $_POST['widget_content'] ?? '',
                    'widget_image' => $_POST['widget_image'] ?? '',
                    'widget_url' => $_POST['widget_url'] ?? '',
                    'widget_column' => (int)($_POST['widget_column'] ?? 1),
                    'widget_order' => (int)($_POST['widget_order'] ?? 0),
                    'widget_width' => $_POST['widget_width'] ?? 'full',
                    'widget_style' => $_POST['widget_style'] ?? '',
                    'is_active' => !empty($_POST['is_active']) ? 1 : 0
                ];
                
                if ($widgetModel->update($widgetId, $widgetData)) {
                    $message = 'Widget updated successfully!';
                } else {
                    $error = 'Failed to update widget';
                }
            }
        }
        
        elseif ($action === 'delete_widget') {
            $widgetId = (int)($_POST['widget_id'] ?? 0);
            if ($widgetId > 0 && $widgetModel->delete($widgetId)) {
                $message = 'Widget deleted successfully!';
            } else {
                $error = 'Failed to delete widget';
            }
        }
        
        elseif ($action === 'add_product') {
            $productId = (int)($_POST['product_id'] ?? 0);
            if ($productId > 0) {
                try {
                    $db = \App\Database\Connection::getInstance();
                    $db->insert('mega_menu_products', [
                        'menu_item_id' => $menuItemId,
                        'product_id' => $productId,
                        'display_order' => (int)($_POST['display_order'] ?? 0)
                    ]);
                    $message = 'Product added to mega menu!';
                } catch (\Exception $e) {
                    $error = 'Failed to add product: ' . $e->getMessage();
                }
            }
        }
        
        elseif ($action === 'remove_product') {
            $productId = (int)($_POST['product_id'] ?? 0);
            if ($productId > 0) {
                try {
                    $db = \App\Database\Connection::getInstance();
                    $db->delete('mega_menu_products', 'menu_item_id = :menu_item_id AND product_id = :product_id', [
                        'menu_item_id' => $menuItemId,
                        'product_id' => $productId
                    ]);
                    $message = 'Product removed from mega menu!';
                } catch (\Exception $e) {
                    $error = 'Failed to remove product: ' . $e->getMessage();
                }
            }
        }
        
        elseif ($action === 'add_category') {
            $categoryId = (int)($_POST['category_id'] ?? 0);
            if ($categoryId > 0) {
                try {
                    $db = \App\Database\Connection::getInstance();
                    $db->insert('mega_menu_categories', [
                        'menu_item_id' => $menuItemId,
                        'category_id' => $categoryId,
                        'display_order' => (int)($_POST['display_order'] ?? 0),
                        'show_image' => !empty($_POST['show_image']) ? 1 : 0,
                        'show_description' => !empty($_POST['show_description']) ? 1 : 0
                    ]);
                    $message = 'Category added to mega menu!';
                } catch (\Exception $e) {
                    $error = 'Failed to add category: ' . $e->getMessage();
                }
            }
        }
        
        elseif ($action === 'remove_category') {
            $categoryId = (int)($_POST['category_id'] ?? 0);
            if ($categoryId > 0) {
                try {
                    $db = \App\Database\Connection::getInstance();
                    $db->delete('mega_menu_categories', 'menu_item_id = :menu_item_id AND category_id = :category_id', [
                        'menu_item_id' => $menuItemId,
                        'category_id' => $categoryId
                    ]);
                    $message = 'Category removed from mega menu!';
                } catch (\Exception $e) {
                    $error = 'Failed to remove category: ' . $e->getMessage();
                }
            }
        }
        
        // Redirect to prevent resubmission
        if (!empty($message) || !empty($error)) {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?menu_item_id=' . $menuItemId . (!empty($message) ? '&msg=' . urlencode($message) : '') . (!empty($error) ? '&err=' . urlencode($error) : ''));
            exit;
        }
    }
}

// Get messages from URL
if (!empty($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
}
if (!empty($_GET['err'])) {
    $error = urldecode($_GET['err']);
}

// Load data
$widgets = [];
$products = [];
$categories = [];
$allProducts = [];
$allCategories = [];

if ($menuItemId > 0 && $menuItem) {
    $widgets = $widgetModel->getByMenuItemId($menuItemId);
    
    // Get products in mega menu
    try {
        $db = \App\Database\Connection::getInstance();
        $products = $db->fetchAll(
            "SELECT p.*, mmp.display_order 
             FROM products p
             INNER JOIN mega_menu_products mmp ON p.id = mmp.product_id
             WHERE mmp.menu_item_id = :menu_item_id AND p.is_active = 1
             ORDER BY mmp.display_order ASC",
            ['menu_item_id' => $menuItemId]
        );
    } catch (\Exception $e) {
        $products = [];
    }
    
    // Get categories in mega menu
    try {
        $db = \App\Database\Connection::getInstance();
        $categories = $db->fetchAll(
            "SELECT c.*, mmc.display_order, mmc.show_image, mmc.show_description
             FROM categories c
             INNER JOIN mega_menu_categories mmc ON c.id = mmc.category_id
             WHERE mmc.menu_item_id = :menu_item_id AND c.is_active = 1
             ORDER BY mmc.display_order ASC",
            ['menu_item_id' => $menuItemId]
        );
    } catch (\Exception $e) {
        $categories = [];
    }
    
    // Get all products and categories for selection
    $allProducts = $productModel->getAll(['is_active' => 1]);
    $allCategories = $categoryModel->getAll(true);
}

include __DIR__ . '/includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-th-large text-purple-600 mr-3"></i>Mega Menu Manager
                </h1>
                <?php if ($menuItem): ?>
                <p class="text-gray-600 mt-2">
                    Managing mega menu for: <strong><?= escape($menuItem['title']) ?></strong>
                </p>
                <?php endif; ?>
            </div>
            <?php if ($menuItem): ?>
            <a href="menu-edit.php?id=<?= $menuItem['menu_id'] ?>" class="btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back to Menu Editor
            </a>
            <?php endif; ?>
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
        
        <?php if (!$menuItemId || !$menuItem): ?>
        <div class="text-center py-12">
            <i class="fas fa-exclamation-triangle text-yellow-500 text-6xl mb-4"></i>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">No Menu Item Selected</h2>
            <p class="text-gray-600 mb-6">Please select a menu item from the menu editor to manage its mega menu content.</p>
            <a href="menus.php" class="btn-primary inline-block">
                <i class="fas fa-list mr-2"></i>Go to Menus
            </a>
        </div>
        <?php else: ?>
        
        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="flex space-x-8">
                <button onclick="showTab('widgets')" class="tab-button active py-4 px-1 border-b-2 border-purple-600 font-medium text-sm text-purple-600">
                    <i class="fas fa-puzzle-piece mr-2"></i>Widgets
                </button>
                <button onclick="showTab('products')" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <i class="fas fa-box mr-2"></i>Products
                </button>
                <button onclick="showTab('categories')" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <i class="fas fa-folder mr-2"></i>Categories
                </button>
            </nav>
        </div>
        
        <!-- Widgets Tab -->
        <div id="widgetsTab" class="tab-content">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">Custom Widgets</h2>
                <button onclick="openAddWidgetModal()" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i>Add Widget
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($widgets as $widget): ?>
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="font-semibold text-gray-800"><?= escape($widget['widget_title'] ?: 'Untitled Widget') ?></h3>
                        <div class="flex gap-2">
                            <button onclick="editWidget(<?= $widget['id'] ?>)" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteWidget(<?= $widget['id'] ?>)" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mb-2">
                        <span class="badge badge-blue"><?= escape($widget['widget_type']) ?></span>
                        <span class="badge badge-gray">Column <?= $widget['widget_column'] ?></span>
                    </p>
                    <?php if (!empty($widget['widget_content'])): ?>
                    <p class="text-sm text-gray-700 line-clamp-2"><?= escape(substr($widget['widget_content'], 0, 100)) ?>...</p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($widgets)): ?>
                <div class="col-span-full text-center py-8 text-gray-500">
                    <i class="fas fa-puzzle-piece text-4xl mb-2"></i>
                    <p>No widgets yet. Click "Add Widget" to create one.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Products Tab -->
        <div id="productsTab" class="tab-content hidden">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">Featured Products</h2>
                <button onclick="openAddProductModal()" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i>Add Product
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($products as $product): ?>
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="flex items-center gap-4">
                        <?php if (!empty($product['image'])): ?>
                        <img src="<?= escape(image_url($product['image'])) ?>" alt="<?= escape($product['name']) ?>" class="w-16 h-16 object-cover rounded">
                        <?php endif; ?>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800"><?= escape($product['name']) ?></h3>
                            <?php if (!empty($product['price'])): ?>
                            <p class="text-sm text-gray-600">$<?= number_format($product['price'], 2) ?></p>
                            <?php endif; ?>
                        </div>
                        <form method="POST" onsubmit="return confirm('Remove this product from mega menu?')">
                            <input type="hidden" name="action" value="remove_product">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($products)): ?>
                <div class="col-span-full text-center py-8 text-gray-500">
                    <i class="fas fa-box text-4xl mb-2"></i>
                    <p>No products added yet. Click "Add Product" to add featured products.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Categories Tab -->
        <div id="categoriesTab" class="tab-content hidden">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">Categories</h2>
                <button onclick="openAddCategoryModal()" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i>Add Category
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($categories as $category): ?>
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="flex items-center gap-4">
                        <?php if (!empty($category['image']) && !empty($category['show_image'])): ?>
                        <img src="<?= escape(image_url($category['image'])) ?>" alt="<?= escape($category['name']) ?>" class="w-16 h-16 object-cover rounded">
                        <?php endif; ?>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800"><?= escape($category['name']) ?></h3>
                            <?php if (!empty($category['description']) && !empty($category['show_description'])): ?>
                            <p class="text-sm text-gray-600 line-clamp-2"><?= escape($category['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        <form method="POST" onsubmit="return confirm('Remove this category from mega menu?')">
                            <input type="hidden" name="action" value="remove_category">
                            <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($categories)): ?>
                <div class="col-span-full text-center py-8 text-gray-500">
                    <i class="fas fa-folder text-4xl mb-2"></i>
                    <p>No categories added yet. Click "Add Category" to add categories.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<!-- Add Widget Modal -->
<div id="addWidgetModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white p-6 rounded-t-xl flex items-center justify-between">
            <h3 class="text-xl font-bold">Add Widget</h3>
            <button onclick="closeAddWidgetModal()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="action" value="add_widget">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Widget Type</label>
                    <select name="widget_type" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg" required>
                        <option value="text">Text</option>
                        <option value="image">Image</option>
                        <option value="html">HTML</option>
                        <option value="button">Button</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Title</label>
                    <input type="text" name="widget_title" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Content</label>
                    <textarea name="widget_content" rows="4" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Image URL (for image type)</label>
                    <input type="text" name="widget_image" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Link URL</label>
                    <input type="text" name="widget_url" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Column</label>
                        <input type="number" name="widget_column" min="1" max="6" value="1" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Order</label>
                        <input type="number" name="widget_order" value="0" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Width</label>
                    <select name="widget_width" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg">
                        <option value="full">Full</option>
                        <option value="half">Half</option>
                        <option value="third">Third</option>
                        <option value="quarter">Quarter</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                <button type="button" onclick="closeAddWidgetModal()" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Add Widget</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Product Modal -->
<div id="addProductModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-6 rounded-t-xl flex items-center justify-between">
            <h3 class="text-xl font-bold">Add Product</h3>
            <button onclick="closeAddProductModal()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="action" value="add_product">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select Product</label>
                    <select name="product_id" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg" required>
                        <option value="">-- Select Product --</option>
                        <?php foreach ($allProducts as $product): ?>
                        <option value="<?= $product['id'] ?>"><?= escape($product['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Display Order</label>
                    <input type="number" name="display_order" value="0" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg">
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                <button type="button" onclick="closeAddProductModal()" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Add Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-green-600 to-teal-600 text-white p-6 rounded-t-xl flex items-center justify-between">
            <h3 class="text-xl font-bold">Add Category</h3>
            <button onclick="closeAddCategoryModal()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="action" value="add_category">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select Category</label>
                    <select name="category_id" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($allCategories as $category): ?>
                        <option value="<?= $category['id'] ?>"><?= escape($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Display Order</label>
                    <input type="number" name="display_order" value="0" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg">
                </div>
                <div class="flex gap-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="show_image" value="1" checked class="mr-2">
                        <span class="text-sm text-gray-700">Show Image</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="show_description" value="1" class="mr-2">
                        <span class="text-sm text-gray-700">Show Description</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                <button type="button" onclick="closeAddCategoryModal()" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Add Category</button>
            </div>
        </form>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active', 'border-purple-600', 'text-purple-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab
    document.getElementById(tabName + 'Tab').classList.remove('hidden');
    
    // Activate button
    event.target.classList.add('active', 'border-purple-600', 'text-purple-600');
    event.target.classList.remove('border-transparent', 'text-gray-500');
}

function openAddWidgetModal() {
    document.getElementById('addWidgetModal').classList.remove('hidden');
}

function closeAddWidgetModal() {
    document.getElementById('addWidgetModal').classList.add('hidden');
}

function openAddProductModal() {
    document.getElementById('addProductModal').classList.remove('hidden');
}

function closeAddProductModal() {
    document.getElementById('addProductModal').classList.add('hidden');
}

function openAddCategoryModal() {
    document.getElementById('addCategoryModal').classList.remove('hidden');
}

function closeAddCategoryModal() {
    document.getElementById('addCategoryModal').classList.add('hidden');
}

function editWidget(id) {
    // TODO: Implement edit widget functionality
    alert('Edit widget functionality coming soon!');
}

function deleteWidget(id) {
    if (confirm('Delete this widget?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_widget">
            <input type="hidden" name="widget_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
