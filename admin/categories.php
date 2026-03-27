<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Category;

$categoryModel = new Category();
$message = '';
$error = '';

// Handle clear all parents action
if (!empty($_GET['action']) && $_GET['action'] === 'clear_all_parents') {
    try {
        // Count how many will be affected
        $count = db()->fetchOne("SELECT COUNT(*) as count FROM categories WHERE parent_id IS NOT NULL")['count'] ?? 0;
        
        if ($count > 0) {
            // Use direct SQL query since we need to update all rows with parent_id IS NOT NULL
            $stmt = db()->query("UPDATE categories SET parent_id = NULL WHERE parent_id IS NOT NULL");
            $affected = $stmt->rowCount();
            
            if ($affected > 0) {
                $message = "Successfully cleared parent relationships from {$affected} categor" . ($affected == 1 ? 'y' : 'ies') . ". All categories are now top-level.";
            } else {
                $error = 'Failed to clear parent categories.';
            }
        } else {
            $message = 'No categories have parent relationships to clear.';
        }
    } catch (\Exception $e) {
        $error = 'Error clearing parent categories: ' . $e->getMessage();
    }
}


if (!empty($_GET['delete'])) {
    try {
        $categoryId = (int)$_GET['delete'];
        
        // Validate ID
        if ($categoryId <= 0) {
            $error = 'Invalid category ID.';
        } else {
            // Check if category exists
            $category = $categoryModel->getById($categoryId);
            if (!$category) {
                $error = 'Category not found.';
            } else {
                // Get all descendants (sub-categories at all levels)
                $descendants = $categoryModel->getDescendants($categoryId, false);
                $subCategoryCount = count($descendants);
                
                if ($subCategoryCount > 0) {
                    $error = "Cannot delete category. It has {$subCategoryCount} sub-category(ies). Please delete or reassign sub-categories first.";
                } else {
                    // Check if category has products
                    $productCount = $categoryModel->getProductCount($categoryId, false);
                    if ($productCount > 0) {
                        $error = "Cannot delete category. It has {$productCount} product(s) assigned. Please reassign or delete products first.";
                    } else {
                        // Safe to delete
                        $deleted = $categoryModel->delete($categoryId);
                        if ($deleted) {
                            $message = 'Category deleted successfully.';
                        } else {
                            $error = 'Failed to delete category.';
                        }
                    }
                }
            }
        }
    } catch (\Exception $e) {
        $error = 'Error deleting category: ' . $e->getMessage();
    }
}

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'name_asc';

// Get all categories
$allCategories = $categoryModel->getAll(false);

// Get hierarchical tree for display
$categoryTree = $categoryModel->getTree(null, false);

// Apply filters
$categories = $allCategories;

if ($search) {
    $categories = array_filter($categories, function($cat) use ($search) {
        return stripos($cat['name'], $search) !== false || 
               stripos($cat['slug'], $search) !== false ||
               stripos($cat['description'] ?? '', $search) !== false;
    });
}

if ($statusFilter === 'active') {
    $categories = array_filter($categories, fn($c) => $c['is_active'] == 1);
} elseif ($statusFilter === 'inactive') {
    $categories = array_filter($categories, fn($c) => $c['is_active'] == 0);
}

// Sort
switch ($sort) {
    case 'name_asc':
        usort($categories, fn($a, $b) => strcmp($a['name'], $b['name']));
        break;
    case 'name_desc':
        usort($categories, fn($a, $b) => strcmp($b['name'], $a['name']));
        break;
    case 'date_desc':
        usort($categories, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        break;
}

// Column visibility
$selectedColumns = $_GET['columns'] ?? ['image', 'name', 'slug', 'status', 'actions'];
// Check if short_description column exists
$hasShortDescription = false;
try {
    db()->fetchOne("SELECT short_description FROM categories LIMIT 1");
    $hasShortDescription = true;
} catch (\Exception $e) {
    $hasShortDescription = false;
}

$availableColumns = [
    'image' => 'Image',
    'name' => 'Name',
    'slug' => 'Slug',
    'description' => 'Description',
    'status' => 'Status',
    'products' => 'Products Count',
    'created' => 'Created Date',
    'actions' => 'Actions'
];

if ($hasShortDescription) {
    $availableColumns['short_description'] = 'Short Description';
}

// Calculate stats for mini dashboard
$totalCategories = count($allCategories);
$activeCategories = count(array_filter($allCategories, fn($c) => $c['is_active'] == 1));
$inactiveCategories = $totalCategories - $activeCategories;

// Count categories with parents
$categoriesWithParents = count(array_filter($allCategories, fn($c) => !empty($c['parent_id'])));

// Count products per category
$categoriesWithProducts = 0;
foreach ($allCategories as $cat) {
    $productCount = db()->fetchOne(
        "SELECT COUNT(*) as count FROM products WHERE category_id = :id",
        ['id' => $cat['id']]
    )['count'] ?? 0;
    if ($productCount > 0) {
        $categoriesWithProducts++;
    }
}

$miniStats = [
    [
        'label' => 'Total Categories',
        'value' => number_format($totalCategories),
        'icon' => 'fas fa-tags',
        'color' => 'from-green-500 to-emerald-600',
        'description' => 'All categories',
        'link' => url('admin/categories.php')
    ],
    [
        'label' => 'Active Categories',
        'value' => number_format($activeCategories),
        'icon' => 'fas fa-check-circle',
        'color' => 'from-blue-500 to-cyan-600',
        'description' => 'Currently active',
        'link' => url('admin/categories.php?status=active')
    ],
    [
        'label' => 'With Products',
        'value' => number_format($categoriesWithProducts),
        'icon' => 'fas fa-box',
        'color' => 'from-purple-500 to-indigo-600',
        'description' => 'Have products assigned',
        'link' => url('admin/categories.php')
    ],
    [
        'label' => 'Inactive',
        'value' => number_format($inactiveCategories),
        'icon' => 'fas fa-ban',
        'color' => 'from-gray-500 to-gray-600',
        'description' => 'Currently inactive',
        'link' => url('admin/categories.php?status=inactive')
    ]
];

$pageTitle = 'Categories';
include __DIR__ . '/includes/header.php';

// Setup filter component
$filterId = 'categories-filter';
$filters = [
    'search' => true,
    'status' => [
        'options' => [
            'all' => 'All Statuses',
            'active' => 'Active Only',
            'inactive' => 'Inactive Only'
        ]
    ]
];
$sortOptions = [
    'name_asc' => 'Name (A-Z)',
    'name_desc' => 'Name (Z-A)',
    'date_desc' => 'Newest First'
];
$defaultColumns = ['name', 'slug', 'status', 'actions'];
?>

<div class="w-full">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-600 to-emerald-600 rounded-xl shadow-xl p-4 md:p-6 lg:p-8 mb-4 md:mb-6 text-white">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-1 md:mb-2">
                    <i class="fas fa-tags mr-2 md:mr-3"></i>
                    Categories Management
                </h1>
                <p class="text-green-100 text-sm md:text-lg">Organize your products into categories</p>
            </div>
            <a href="<?= url('admin/category-edit.php') ?>" class="btn-primary w-full sm:w-auto text-center">
                <i class="fas fa-plus"></i>
                Add New Category
            </a>
        </div>
    </div>

    <!-- Mini Dashboard Stats -->
    <?php 
    $stats = $miniStats;
    include __DIR__ . '/includes/mini-stats.php'; 
    ?>

    <?php if (!empty($message)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2 text-xl"></i>
            <span class="font-semibold"><?= escape($message) ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2 text-xl"></i>
            <span class="font-semibold"><?= escape($error) ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Advanced Filters -->
    <?php include __DIR__ . '/includes/advanced-filters.php'; ?>
    
    <!-- Stats Bar -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-6">
                <div>
                    <span class="text-sm text-gray-600">Total Categories:</span>
                    <span class="ml-2 font-bold text-gray-900"><?= count($allCategories) ?></span>
                </div>
                <div>
                    <span class="text-sm text-gray-600">Showing:</span>
                    <span class="ml-2 font-bold text-green-600"><?= count($categories) ?></span>
                </div>
                <?php if ($categoriesWithParents > 0): ?>
                <div>
                    <span class="text-sm text-gray-600">With Parents:</span>
                    <span class="ml-2 font-bold text-orange-600"><?= $categoriesWithParents ?></span>
                </div>
                <?php endif; ?>
            </div>
            <div class="relative">
                <button type="button" 
                        onclick="toggleBulkActions()"
                        class="btn-primary">
                    <i class="fas fa-cog"></i>
                    <span>Bulk Actions</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div id="bulkActionsDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-xl z-50 border border-gray-200">
                    <div class="p-2">
                        <?php if ($categoriesWithParents > 0): ?>
                        <a href="#" 
                           onclick="clearAllParents(); return false;"
                           class="block px-4 py-3 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 rounded-lg transition-colors">
                            <i class="fas fa-unlink mr-2"></i>
                            <span class="font-semibold">Clear All Parent Categories</span>
                            <p class="text-xs text-gray-500 mt-1">Remove parent relationships from all categories (<?= $categoriesWithParents ?> categories affected)</p>
                        </a>
                        <?php else: ?>
                        <div class="px-4 py-3 text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-2"></i>
                            No categories have parent relationships to clear.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Categories Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto -mx-4 md:mx-0">
            <div class="inline-block min-w-full align-middle">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                <tr>
                    <!-- Icon Column - Always visible -->
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="icon">
                        <i class="fas fa-icons mr-1"></i>Icon
                    </th>
                    
                    <?php if (in_array('image', $selectedColumns)): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="image">Image</th>
                    <?php endif; ?>
                    
                    <?php if (in_array('name', $selectedColumns) || empty($_GET['columns'])): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="name">
                        <i class="fas fa-sitemap mr-1"></i>Name / Hierarchy
                    </th>
                    <?php endif; ?>
                    
                    <?php if (in_array('slug', $selectedColumns) || empty($_GET['columns'])): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="slug">Slug</th>
                    <?php endif; ?>
                    
                    <?php 
                    // Check if short_description column exists
                    $hasShortDescription = false;
                    try {
                        db()->fetchOne("SELECT short_description FROM categories LIMIT 1");
                        $hasShortDescription = true;
                    } catch (\Exception $e) {
                        $hasShortDescription = false;
                    }
                    ?>
                    <?php if ($hasShortDescription && in_array('short_description', $selectedColumns)): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="short_description">Short Description</th>
                    <?php endif; ?>
                    
                    <?php if (in_array('description', $selectedColumns)): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="description">Description</th>
                    <?php endif; ?>
                    
                    <?php if (in_array('status', $selectedColumns) || empty($_GET['columns'])): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="status">Status</th>
                    <?php endif; ?>
                    
                    <?php if (in_array('products', $selectedColumns)): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="products">Products</th>
                    <?php endif; ?>
                    
                    <?php if (in_array('created', $selectedColumns)): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="created">Created</th>
                    <?php endif; ?>
                    
                    <?php if (in_array('actions', $selectedColumns) || empty($_GET['columns'])): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="actions">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200" id="categories-tbody">
                <?php if (empty($categoryTree)): ?>
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                            <div class="py-8">
                                <i class="fas fa-folder-open text-gray-300 text-6xl mb-4"></i>
                                <p class="text-lg font-semibold text-gray-700 mb-2">No Categories</p>
                                <p class="text-gray-500 mb-4">Create your first category to get started.</p>
                                <a href="<?= url('admin/category-edit.php') ?>" class="btn-primary">
                                    <i class="fas fa-plus"></i>Add First Category
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    // Render categories in hierarchical tree
                    function renderCategoryTree($categoryTree, $categoryModel, $selectedColumns, $level = 0) {
                        $html = '';
                        foreach ($categoryTree as $category) {
                            // Get product count (including sub-categories)
                            $productCount = $categoryModel->getProductCount($category['id'], true);
                            $directProductCount = $categoryModel->getProductCount($category['id'], false);
                            $subCategoryCount = count($categoryModel->getChildren($category['id'], false));
                            
                            // Get parent info
                            $parent = null;
                            if (!empty($category['parent_id'])) {
                                $parent = $categoryModel->getById($category['parent_id']);
                            }
                            
                            // Get category path
                            $categoryPath = $categoryModel->getPath($category['id']);
                            
                            // Get automatic icon
                            $categoryIcons = [
                                'forklift' => 'fa-truck',
                                'electric' => 'fa-bolt',
                                'diesel' => 'fa-gas-pump',
                                'gas' => 'fa-fire',
                                'ic' => 'fa-cog',
                                'li-ion' => 'fa-battery-full',
                                'attachment' => 'fa-puzzle-piece',
                                'pallet' => 'fa-boxes',
                                'stacker' => 'fa-layer-group',
                                'reach' => 'fa-arrow-up',
                            ];
                            
                            $categoryName = strtolower($category['name']);
                            $icon = 'fa-box';
                            foreach ($categoryIcons as $key => $iconClass) {
                                if (strpos($categoryName, $key) !== false) {
                                    $icon = $iconClass;
                                    break;
                                }
                            }
                            
                            $indentClass = $level > 0 ? 'pl-' . ($level * 6) : '';
                            $hasChildren = !empty($category['children']);
                            
                            $html .= '<tr class="category-row ' . ($hasChildren ? 'bg-blue-50/30' : '') . ' hover:bg-gray-50 transition-colors" data-category-id="' . $category['id'] . '" data-parent-id="' . ($category['parent_id'] ?? '') . '" data-level="' . $level . '" data-sort-order="' . ($category['sort_order'] ?? 0) . '">';
                            
                            // Icon Column
                            $html .= '<td class="px-6 py-4 whitespace-nowrap" data-column="icon">';
                            $html .= '<div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-lg border-2 border-blue-300 flex items-center justify-center shadow-sm">';
                            $html .= '<i class="fas ' . escape($icon) . ' text-blue-700 text-2xl" title="' . escape($category['name']) . '"></i>';
                            $html .= '</div></td>';
                            
                            // Image Column
                            if (in_array('image', $selectedColumns)) {
                                $html .= '<td class="px-6 py-4 whitespace-nowrap" data-column="image">';
                                if (!empty($category['image'])) {
                                    $html .= '<img src="' . escape(image_url($category['image'])) . '" alt="' . escape($category['name']) . '" class="w-16 h-16 object-cover rounded-lg border border-gray-200">';
                                } else {
                                    $html .= '<div class="w-16 h-16 bg-gray-100 rounded-lg border border-gray-200 flex items-center justify-center"><span class="text-xs text-gray-500">No Image</span></div>';
                                }
                                $html .= '</td>';
                            }
                            
                            // Name Column with Hierarchy
                            if (in_array('name', $selectedColumns) || empty($_GET['columns'])) {
                                $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium ' . $indentClass . '" data-column="name">';
                                $html .= '<div class="flex items-center gap-2 category-name-content">';
                                if ($level > 0) {
                                    // Show indentation indicators
                                    for ($i = 0; $i < $level; $i++) {
                                        $html .= '<i class="fas fa-chevron-right text-gray-300 text-xs"></i>';
                                    }
                                    $html .= '<i class="fas fa-folder-open mr-2 text-indigo-500"></i>';
                                } else {
                                    $html .= '<i class="fas fa-folder mr-2 text-blue-600"></i>';
                                }
                                $html .= '<span class="font-semibold text-gray-900">' . escape($category['name']) . '</span>';
                                if ($hasChildren) {
                                    $html .= '<span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-semibold">' . count($category['children']) . ' sub</span>';
                                }
                                if ($subCategoryCount > 0) {
                                    $html .= '<span class="ml-1 text-xs text-gray-500">(' . $subCategoryCount . ' sub-categories)</span>';
                                }
                                $html .= '</div>';
                                if ($parent && $level > 0) {
                                    $html .= '<div class="text-xs text-gray-500 mt-1 category-parent-info">Parent: ' . escape($parent['name']) . '</div>';
                                }
                                $html .= '</td>';
                            }
                            
                            // Slug Column
                            if (in_array('slug', $selectedColumns) || empty($_GET['columns'])) {
                                $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-column="slug">';
                                $html .= '<code class="px-2 py-1 bg-gray-100 rounded text-xs">' . escape($category['slug']) . '</code>';
                                $html .= '</td>';
                            }
                            
                            // Status Column
                            if (in_array('status', $selectedColumns) || empty($_GET['columns'])) {
                                $html .= '<td class="px-6 py-4 whitespace-nowrap" data-column="status">';
                                $html .= '<span class="px-2 py-1 text-xs rounded ' . ($category['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') . '">';
                                $html .= $category['is_active'] ? 'Active' : 'Inactive';
                                $html .= '</span></td>';
                            }
                            
                            // Products Column
                            if (in_array('products', $selectedColumns)) {
                                $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-column="products">';
                                $html .= '<div class="flex flex-col">';
                                $html .= '<span class="font-semibold">' . number_format($productCount) . '</span>';
                                if ($productCount > $directProductCount) {
                                    $html .= '<span class="text-xs text-gray-400">(' . number_format($directProductCount) . ' direct)</span>';
                                }
                                $html .= '</div></td>';
                            }
                            
                            // Created Column
                            if (in_array('created', $selectedColumns)) {
                                $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-column="created">';
                                $html .= date('M d, Y', strtotime($category['created_at']));
                                $html .= '</td>';
                            }
                            
                            // Actions Column
                            if (in_array('actions', $selectedColumns) || empty($_GET['columns'])) {
                                $html .= '<td class="px-6 py-4 whitespace-nowrap" data-column="actions">';
                                $html .= '<div class="flex items-center space-x-2">';
                                $html .= '<a href="' . url('admin/category-edit.php?id=' . $category['id']) . '" class="action-btn action-btn-edit" title="Edit"><i class="fas fa-edit"></i></a>';
                                if ($hasChildren) {
                                    $html .= '<span class="action-btn bg-indigo-100 text-indigo-700 cursor-default" title="Has ' . count($category['children']) . ' Sub-Categories"><i class="fas fa-sitemap"></i></span>';
                                }
                                $descendants = $categoryModel->getDescendants($category['id'], false);
                                $hasDescendants = !empty($descendants);
                                $deleteMsg = $hasDescendants ? 'Delete this category and all ' . count($descendants) . ' sub-categories?' : 'Delete this category?';
                                $html .= '<a href="#" onclick="deleteCategory(' . $category['id'] . ', \'' . escape($deleteMsg) . '\'); return false;" class="action-btn action-btn-delete" title="Delete"><i class="fas fa-trash"></i></a>';
                                $html .= '</div></td>';
                            }
                            
                            $html .= '</tr>';
                            
                            // Render children recursively
                            if ($hasChildren) {
                                $html .= renderCategoryTree($category['children'], $categoryModel, $selectedColumns, $level + 1);
                            }
                        }
                        return $html;
                    }
                    
                    echo renderCategoryTree($categoryTree, $categoryModel, $selectedColumns);
                    ?>
                <?php endif; ?>
            </tbody>
        </table>
            </div>
        </div>
    </div>
</div>

<style>
.category-row {
    transition: background-color 0.2s ease;
}

.category-row.bg-blue-50\/30 {
    background-color: rgba(239, 246, 255, 0.3);
}

.category-row {
    position: relative;
}

.category-row[data-level="1"] {
    border-left: 3px solid #3b82f6;
}

.category-row[data-level="2"] {
    border-left: 3px solid #6366f1;
}

.category-row[data-level="3"] {
    border-left: 3px solid #8b5cf6;
}
</style>


<script>
function toggleBulkActions() {
    const dropdown = document.getElementById('bulkActionsDropdown');
    dropdown.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('bulkActionsDropdown');
    const button = event.target.closest('button[onclick="toggleBulkActions()"]');
    
    if (!button && !dropdown.contains(event.target)) {
        dropdown.classList.add('hidden');
    }
});

async function clearAllParents() {
    const count = <?= $categoriesWithParents ?>;
    const confirmed = await customConfirm(
        'Are you sure you want to clear all parent categories? This will make all ' + count + ' categories with parents become top-level categories. This action cannot be undone.',
        'Clear All Parent Categories'
    );
    if (confirmed) {
        window.location.href = '<?= url('admin/categories.php?action=clear_all_parents') ?>';
    }
}

async function deleteCategory(categoryId, message) {
    const confirmed = await customConfirm(message, 'Delete Category');
    if (confirmed) {
        window.location.href = '?delete=' + categoryId;
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
