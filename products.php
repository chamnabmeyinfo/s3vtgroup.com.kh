<?php
require_once __DIR__ . '/bootstrap/app.php';

// Check under construction mode
use App\Helpers\UnderConstruction;
UnderConstruction::show();

use App\Models\Product;
use App\Models\Category;

$productModel = new Product();
$categoryModel = new Category();

$filters = [
    'page' => $_GET['page'] ?? 1,
    'limit' => 12
];

if (!empty($_GET['category'])) {
    $category = $categoryModel->getBySlug($_GET['category']);
    if ($category) {
        $filters['category_id'] = $category['id'];
        $filters['include_subcategories'] = true; // Include products from sub-categories
        $categoryName = $category['name'];
        
        // Get breadcrumbs for category
        $categoryBreadcrumbs = $categoryModel->getBreadcrumbs($category['id']);
        
        // Get sub-categories for display
        $subCategories = $categoryModel->getChildren($category['id'], true);
    }
}

if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

if (!empty($_GET['featured'])) {
    $filters['featured'] = true;
}

// Advanced filters
if (!empty($_GET['min_price'])) {
    $filters['min_price'] = (float)$_GET['min_price'];
}

if (!empty($_GET['max_price'])) {
    $filters['max_price'] = (float)$_GET['max_price'];
}

if (!empty($_GET['sort'])) {
    $filters['sort'] = $_GET['sort'];
}

if (!empty($_GET['in_stock'])) {
    $filters['in_stock'] = true;
}

// Get price range for filter
$allProductsForRange = $productModel->getAll([]);
$prices = [];
foreach ($allProductsForRange as $p) {
    $price = !empty($p['sale_price']) ? (float)$p['sale_price'] : (!empty($p['price']) ? (float)$p['price'] : null);
    if ($price !== null) {
        $prices[] = $price;
    }
}
$minPriceRange = !empty($prices) ? min($prices) : 0;
$maxPriceRange = !empty($prices) ? max($prices) : 10000;

$products = $productModel->getAll($filters);
$totalProducts = $productModel->count($filters);
$totalPages = ceil($totalProducts / $filters['limit']);
// Get categories in tree format for sidebar
$categoryTree = $categoryModel->getTree(null, true);
$categories = $categoryModel->getAll(true);

// Canonical URL to avoid duplicate-without-canonical (category or base products)
if (!empty($_GET['category']) && !empty($category)) {
    $canonicalUrl = url('products.php?category=' . rawurlencode($category['slug']));
} elseif (empty($_GET['search']) && (empty($_GET['page']) || ($_GET['page'] ?? 1) == 1)) {
    $canonicalUrl = url('products.php');
}

$pageTitle = 'Products - ' . get_site_name();
$metaDescription = 'Browse our selection of forklifts and industrial equipment';

include __DIR__ . '/includes/header.php';
?>

<main class="app-products-main">
    <div class="products-page-container">
        <!-- Mobile Filter Button - Only visible on mobile -->
        <button onclick="openMobileFilters()" 
                class="mobile-filter-trigger fixed bottom-6 right-6 z-50 bg-blue-600 text-white p-4 rounded-full shadow-2xl hover:bg-blue-700 transition-all duration-300 transform hover:scale-110 md:hidden"
                id="mobile-filter-trigger"
                title="Open Filters">
            <i class="fas fa-filter text-xl"></i>
            <span class="mobile-filter-badge" id="mobile-filter-count">0</span>
        </button>
        
        <div class="products-page-main">
            <!-- Sidebar Filters - Desktop Design -->
            <aside class="sidebar-filters desktop-sidebar flex-shrink-0 transition-all duration-300 ease-in-out" id="sidebar-filters-desktop">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 sticky top-24 overflow-hidden" id="sidebar-content">
                    <!-- Toggle Button - Attached to Sidebar -->
                    <button onclick="toggleSidebar()" 
                            class="w-full bg-blue-600 text-white p-3 hover:bg-blue-700 transition-all duration-300 flex items-center justify-center gap-2 font-semibold"
                            id="sidebar-toggle-btn"
                            title="Toggle Filters">
                        <i class="fas fa-filter" id="sidebar-toggle-icon"></i>
                        <span class="sidebar-toggle-text">Filters</span>
                        <i class="fas fa-chevron-left ml-auto sidebar-chevron" id="sidebar-chevron"></i>
                    </button>
                    
                    <!-- Collapsed Icons View -->
                    <div class="sidebar-collapsed-icons">
                        <button type="button" class="sidebar-icon-btn" onclick="expandAndFocus('filters')" title="Expand Filters">
                            <i class="fas fa-filter"></i>
                        </button>
                        <button type="button" class="sidebar-icon-btn" onclick="expandAndFocus('categories')" title="Categories">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button type="button" class="sidebar-icon-btn" onclick="expandAndFocus('search')" title="Search">
                            <i class="fas fa-search"></i>
                        </button>
                        <button type="button" class="sidebar-icon-btn" onclick="expandAndFocus('price')" title="Price Range">
                            <i class="fas fa-dollar-sign"></i>
                        </button>
                    </div>
                    
                    <!-- Sidebar Content Wrapper -->
                    <div class="sidebar-content-wrapper p-6">
                    
                    <!-- Expanded Full View -->
                    <div class="sidebar-expanded-content">
                        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
                            <h3 class="text-lg font-bold text-gray-800">Filter Products</h3>
                            <button onclick="clearFilters()" class="text-sm text-blue-600 hover:text-blue-700 font-semibold hover:underline transition-colors flex items-center gap-1">
                                <i class="fas fa-redo text-xs"></i>Clear
                            </button>
                        </div>
                    
                        <form method="GET" id="filter-form" class="space-y-6">
                        <!-- Preserve existing params -->
                        <input type="hidden" name="page" value="1">
                        <?php if (!empty($_GET['sort'])): ?>
                            <input type="hidden" name="sort" value="<?= escape($_GET['sort']) ?>">
                        <?php endif; ?>
                        
                        <!-- Search -->
                        <div class="filter-section">
                            <label class="block text-sm font-semibold mb-2 text-gray-700 flex items-center gap-2">
                                <i class="fas fa-search text-blue-600"></i>Search
                            </label>
                            <div class="relative">
                                <input type="text" name="search" 
                                       value="<?= escape($_GET['search'] ?? '') ?>" 
                                       placeholder="Search products..."
                                       class="w-full px-4 py-2.5 pl-10 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-gray-50 focus:bg-white"
                                       onkeyup="debounceFilter()">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>
                        
                        <!-- Price Range -->
                        <div class="filter-section">
                            <label class="block text-sm font-semibold mb-2 text-gray-700 flex items-center gap-2">
                                <i class="fas fa-dollar-sign text-blue-600"></i>Price Range
                            </label>
                            <div class="flex gap-2">
                                <input type="number" name="min_price" 
                                       value="<?= escape($_GET['min_price'] ?? '') ?>" 
                                       placeholder="Min"
                                       min="0"
                                       step="0.01"
                                       class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 focus:bg-white transition-all"
                                       onchange="applyFilters()">
                                <input type="number" name="max_price" 
                                       value="<?= escape($_GET['max_price'] ?? '') ?>" 
                                       placeholder="Max"
                                       min="0"
                                       step="0.01"
                                       class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 focus:bg-white transition-all"
                                       onchange="applyFilters()">
                            </div>
                            <div class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                                <i class="fas fa-info-circle"></i>
                                Range: $<?= number_format($minPriceRange, 2) ?> - $<?= number_format($maxPriceRange, 2) ?>
                            </div>
                        </div>
                        
                        <!-- Categories - Expandable/Collapsible -->
                        <div class="category-filter-section">
                            <button type="button" 
                                    onclick="toggleCategoryFilter()" 
                                    class="w-full flex items-center justify-between p-3 bg-blue-50 hover:bg-blue-100 rounded-xl transition-all duration-300 group border border-blue-200"
                                    id="category-filter-toggle">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-th-large text-blue-600"></i>
                                    <span class="font-semibold text-gray-800">Categories</span>
                                    <span class="text-xs bg-blue-600 text-white px-2 py-0.5 rounded-full font-medium" id="category-count"><?= count($categories) ?></span>
                                </div>
                                <i class="fas fa-chevron-down text-blue-600 transform transition-transform duration-300" id="category-chevron"></i>
                            </button>
                            <div class="category-filter-content hidden mt-3 space-y-1.5" id="category-filter-content">
                                <label class="flex items-center p-2.5 rounded-lg hover:bg-blue-50 transition-all cursor-pointer group/item border border-transparent hover:border-blue-200" onclick="handleCategoryClick(event, '')">
                                    <input type="radio" name="category" value="" 
                                           <?= empty($_GET['category']) ? 'checked' : '' ?>
                                           onchange="applyFilters(event)"
                                           class="mr-3 w-4 h-4 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                    <span class="text-sm font-medium text-gray-700 group-hover/item:text-blue-600 transition-colors flex items-center">
                                        <i class="fas fa-th mr-2 text-blue-600"></i>All Categories
                                    </span>
                                </label>
                                <?php 
                                // Render categories in hierarchical tree (for desktop sidebar)
                                if (!function_exists('renderCategoryFilter')) {
                                    function renderCategoryFilter($categoryTree, $selectedSlug, $level = 0, $mobile = false) {
                                        $html = '';
                                        foreach ($categoryTree as $cat) {
                                            $hasChildren = !empty($cat['children']);
                                            $isSelected = ($selectedSlug ?? '') === ($cat['slug'] ?? '');
                                            
                                            if ($mobile) {
                                                // Mobile format
                                                $html .= '<label class="mobile-filter-option ' . ($isSelected ? 'active' : '') . '">';
                                                $html .= '<input type="radio" name="category" value="' . escape($cat['slug']) . '" ';
                                                $html .= $isSelected ? 'checked' : '';
                                                $html .= ' onchange="applyFilters()" class="mobile-filter-radio">';
                                                $html .= '<span class="mobile-filter-option-text">';
                                                $html .= '<i class="fas ' . ($level > 0 ? 'fa-folder-open' : 'fa-folder') . ' mobile-filter-option-icon"></i>';
                                                $html .= str_repeat('&nbsp;&nbsp;', $level) . ($level > 0 ? '└─ ' : '') . escape($cat['name']);
                                                if ($hasChildren) {
                                                    $html .= ' <span class="text-xs text-gray-400">(' . count($cat['children']) . ')</span>';
                                                }
                                                $html .= '</span>';
                                                if ($isSelected) {
                                                    $html .= '<i class="fas fa-check-circle mobile-filter-check"></i>';
                                                }
                                                $html .= '</label>';
                                            } else {
                                                // Desktop format
                                                $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
                                                $prefix = $level > 0 ? '└─ ' : '';
                                                
                                                $html .= '<label class="flex items-center p-2.5 rounded-lg hover:bg-blue-50 transition-all cursor-pointer group/item border border-transparent hover:border-blue-200 ' . ($isSelected ? 'bg-blue-50 border-blue-200' : '') . '" onclick="handleCategoryClick(event, \'' . escape($cat['slug']) . '\')">';
                                                $html .= '<input type="radio" name="category" value="' . escape($cat['slug']) . '" ';
                                                $html .= $isSelected ? 'checked' : '';
                                                $html .= ' onchange="applyFilters(event)" class="mr-3 w-4 h-4 text-blue-600 focus:ring-blue-500 cursor-pointer">';
                                                $html .= '<span class="text-sm text-gray-700 group-hover/item:text-blue-600 transition-colors flex items-center flex-1">';
                                                if ($level > 0) {
                                                    $html .= '<i class="fas fa-folder-open mr-2 text-indigo-500"></i>';
                                                } else {
                                                    $html .= '<i class="fas fa-folder mr-2 text-blue-600"></i>';
                                                }
                                                $html .= $indent . $prefix . escape($cat['name']);
                                                if ($hasChildren) {
                                                    $html .= ' <span class="ml-2 text-xs bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded">' . count($cat['children']) . ' sub</span>';
                                                }
                                                $html .= '</span>';
                                                if ($isSelected) {
                                                    $html .= '<i class="fas fa-check-circle text-blue-600 ml-auto"></i>';
                                                }
                                                $html .= '</label>';
                                            }
                                            
                                            // Render children
                                            if ($hasChildren) {
                                                $html .= renderCategoryFilter($cat['children'], $selectedSlug, $level + 1, $mobile);
                                            }
                                        }
                                        return $html;
                                    }
                                }
                                echo renderCategoryFilter($categoryTree, $_GET['category'] ?? '', 0, false);
                                ?>
                            </div>
                        </div>
                        
                        <!-- Stock Status -->
                        <div class="filter-section">
                            <label class="block text-sm font-semibold mb-2 text-gray-700 flex items-center gap-2">
                                <i class="fas fa-box-check text-blue-600"></i>Availability
                            </label>
                            <label class="flex items-center p-3 bg-gray-50 hover:bg-blue-50 rounded-xl cursor-pointer transition-colors group">
                                <input type="checkbox" name="in_stock" value="1" 
                                       <?= !empty($_GET['in_stock']) ? 'checked' : '' ?>
                                       onchange="applyFilters()"
                                       class="mr-3 w-4 h-4 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                <span class="text-sm font-medium text-gray-700 group-hover:text-blue-600 transition-colors">In Stock Only</span>
                            </label>
                        </div>
                        
                        <!-- Featured -->
                        <div class="filter-section">
                            <label class="flex items-center p-3 bg-yellow-50 hover:bg-yellow-100 rounded-xl cursor-pointer transition-all border border-yellow-200 group">
                                <input type="checkbox" name="featured" value="1" 
                                       <?= !empty($_GET['featured']) ? 'checked' : '' ?>
                                       onchange="applyFilters()"
                                       class="mr-3 w-4 h-4 text-yellow-500 focus:ring-yellow-500 cursor-pointer">
                                <span class="text-sm font-semibold text-gray-700 group-hover:text-yellow-700 transition-colors flex items-center">
                                    <i class="fas fa-star text-yellow-500 mr-2"></i>Featured Only
                                </span>
                            </label>
                        </div>
                        
                            <button type="submit" class="btn-primary-sm w-full hidden" id="filter-submit">Apply Filters</button>
                        </form>
                    </div>
                    </div>
                </div>
            </aside>
            
            <!-- Mobile Sidebar Filters - Different Design -->
            <aside class="mobile-sidebar-filters fixed inset-0 z-[9999] hidden" id="sidebar-filters-mobile">
                <div class="mobile-sidebar-overlay" onclick="closeMobileFilters()"></div>
                <div class="mobile-sidebar-content">
                    <!-- Mobile Sidebar Header -->
                    <div class="mobile-sidebar-header">
                        <h2 class="mobile-sidebar-title">Filter Products</h2>
                        <button onclick="closeMobileFilters()" class="mobile-sidebar-close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Mobile Sidebar Body -->
                    <div class="mobile-sidebar-body">
                        <form method="GET" id="mobile-filter-form" class="mobile-filter-form">
                            <input type="hidden" name="page" value="1">
                            <?php if (!empty($_GET['sort'])): ?>
                                <input type="hidden" name="sort" value="<?= escape($_GET['sort']) ?>">
                            <?php endif; ?>
                            
                            <!-- Search - Mobile -->
                            <div class="mobile-filter-section">
                                <label class="mobile-filter-label">
                                    <i class="fas fa-search mobile-filter-icon"></i>Search Products
                                </label>
                                <input type="text" name="search" 
                                       value="<?= escape($_GET['search'] ?? '') ?>" 
                                       placeholder="Search products..."
                                       class="mobile-filter-input"
                                       onkeyup="debounceFilter()">
                            </div>
                            
                            <!-- Price Range - Mobile -->
                            <div class="mobile-filter-section">
                                <label class="mobile-filter-label">
                                    <i class="fas fa-dollar-sign mobile-filter-icon"></i>Price Range
                                </label>
                                <div class="mobile-price-inputs">
                                    <input type="number" name="min_price" 
                                           value="<?= escape($_GET['min_price'] ?? '') ?>" 
                                           placeholder="Min"
                                           min="0"
                                           step="0.01"
                                           class="mobile-filter-input"
                                           onchange="applyFilters()">
                                    <span class="mobile-price-separator">to</span>
                                    <input type="number" name="max_price" 
                                           value="<?= escape($_GET['max_price'] ?? '') ?>" 
                                           placeholder="Max"
                                           min="0"
                                           step="0.01"
                                           class="mobile-filter-input"
                                           onchange="applyFilters()">
                                </div>
                                <div class="mobile-price-info">
                                    Range: $<?= number_format($minPriceRange, 2) ?> - $<?= number_format($maxPriceRange, 2) ?>
                                </div>
                            </div>
                            
                            <!-- Categories - Mobile -->
                            <div class="mobile-filter-section">
                                <button type="button" 
                                        onclick="toggleMobileCategoryFilter()" 
                                        class="mobile-filter-toggle"
                                        id="mobile-category-toggle">
                                    <div class="mobile-filter-toggle-content">
                                        <i class="fas fa-th-large mobile-filter-icon"></i>
                                        <span>Categories</span>
                                        <span class="mobile-filter-badge-small"><?= count($categories) ?></span>
                                    </div>
                                    <i class="fas fa-chevron-down mobile-filter-chevron" id="mobile-category-chevron"></i>
                                </button>
                                <div class="mobile-category-content hidden" id="mobile-category-content">
                                    <label class="mobile-filter-option">
                                        <input type="radio" name="category" value="" 
                                               <?= empty($_GET['category']) ? 'checked' : '' ?>
                                               onchange="applyFilters()"
                                               class="mobile-filter-radio">
                                        <span class="mobile-filter-option-text">
                                            <i class="fas fa-th mobile-filter-option-icon"></i>All Categories
                                        </span>
                                    </label>
                                    <?php echo renderCategoryFilter($categoryTree, $_GET['category'] ?? '', 0, true); ?>
                                </div>
                            </div>
                            
                            <!-- Stock Status - Mobile -->
                            <div class="mobile-filter-section">
                                <label class="mobile-filter-checkbox-label">
                                    <input type="checkbox" name="in_stock" value="1" 
                                           <?= !empty($_GET['in_stock']) ? 'checked' : '' ?>
                                           onchange="applyFilters()"
                                           class="mobile-filter-checkbox">
                                    <div class="mobile-filter-checkbox-content">
                                        <i class="fas fa-box-check mobile-filter-icon"></i>
                                        <span>In Stock Only</span>
                                    </div>
                                </label>
                            </div>
                            
                            <!-- Featured - Mobile -->
                            <div class="mobile-filter-section">
                                <label class="mobile-filter-checkbox-label featured">
                                    <input type="checkbox" name="featured" value="1" 
                                           <?= !empty($_GET['featured']) ? 'checked' : '' ?>
                                           onchange="applyFilters()"
                                           class="mobile-filter-checkbox">
                                    <div class="mobile-filter-checkbox-content">
                                        <i class="fas fa-star mobile-filter-icon"></i>
                                        <span>Featured Only</span>
                                    </div>
                                </label>
                            </div>
                            
                            <button type="submit" class="mobile-filter-submit hidden">Apply Filters</button>
                        </form>
                    </div>
                    
                    <!-- Mobile Sidebar Footer -->
                    <div class="mobile-sidebar-footer">
                        <button onclick="clearFilters()" class="mobile-filter-clear">
                            <i class="fas fa-redo"></i>Clear All
                        </button>
                        <button onclick="applyMobileFilters()" class="mobile-filter-apply">
                            <i class="fas fa-check"></i>Apply Filters
                        </button>
                    </div>
                </div>
            </aside>
            
            <!-- Products Grid -->
            <div class="flex-1 transition-all duration-300" id="products-container">
                <!-- Admin Bar (if logged in) -->
                <?php if (session('admin_logged_in')): ?>
                <div class="mb-4 p-3 bg-yellow-50 border-l-4 border-yellow-400 rounded-lg shadow-sm flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm text-yellow-800">
                        <i class="fas fa-user-shield"></i>
                        <span class="font-semibold">Admin Mode:</span>
                        <span>You can feature/unfeature products directly from the product cards</span>
                        <?php if (!empty($categoryName) && !empty($category)): ?>
                            <span class="mx-2">•</span>
                            <span>or feature/unfeature all products in this category</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php if (!empty($categoryName) && !empty($category)): ?>
                            <button onclick="featureCategory('<?= escape($category['slug']) ?>', <?= $category['id'] ?>)" 
                                    class="px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors flex items-center gap-1.5"
                                    id="feature-category-btn-admin"
                                    title="Feature all products in '<?= escape($categoryName) ?>' category">
                                <i class="fas fa-star"></i>
                                <span>Feature All</span>
                            </button>
                            <button onclick="unfeatureCategory('<?= escape($category['slug']) ?>', <?= $category['id'] ?>)" 
                                    class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors flex items-center gap-1.5"
                                    id="unfeature-category-btn-admin"
                                    title="Unfeature all products in '<?= escape($categoryName) ?>' category">
                                <i class="far fa-star"></i>
                                <span>Unfeature All</span>
                            </button>
                        <?php endif; ?>
                        <a href="<?= url('admin/products.php') ?>" 
                           class="px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm flex items-center gap-1 transition-colors">
                            <i class="fas fa-list"></i>
                            <span>Manage Products</span>
                        </a>
                        <a href="<?= url('admin/index.php') ?>" 
                           class="px-3 py-1.5 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm flex items-center gap-1 transition-colors">
                            <i class="fas fa-tachometer-alt"></i>
                            <span class="hidden sm:inline">Dashboard</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Advanced Filters - Top Section (Toggleable) -->
                <div class="advanced-filters-top">
                    <button onclick="toggleAdvancedFilters()" 
                            class="advanced-filters-toggle"
                            id="advanced-filters-toggle-btn"
                            aria-expanded="false">
                        <div class="advanced-filters-toggle-content">
                            <i class="fas fa-sliders-h"></i>
                            <span class="advanced-filters-toggle-text">Advanced Filters</span>
                            <span class="advanced-filters-count" id="advanced-filters-count">0</span>
                        </div>
                        <i class="fas fa-chevron-down advanced-filters-chevron" id="advanced-filters-chevron"></i>
                    </button>
                    
                    <div class="advanced-filters-content" id="advanced-filters-content">
                        <form method="GET" id="advanced-filters-form" class="advanced-filters-form">
                            <input type="hidden" name="page" value="1">
                            <?php if (!empty($_GET['sort'])): ?>
                                <input type="hidden" name="sort" value="<?= escape($_GET['sort']) ?>">
                            <?php endif; ?>
                            
                            <div class="advanced-filters-grid">
                                <!-- Search -->
                                <div class="advanced-filter-item">
                                    <label class="advanced-filter-label">
                                        <i class="fas fa-search"></i>
                                        <span>Search</span>
                                    </label>
                                    <input type="text" 
                                           name="search" 
                                           value="<?= escape($_GET['search'] ?? '') ?>" 
                                           placeholder="Search products..."
                                           class="advanced-filter-input"
                                           onkeyup="debounceFilter()">
                                </div>
                                
                                <!-- Price Range -->
                                <div class="advanced-filter-item">
                                    <label class="advanced-filter-label">
                                        <i class="fas fa-dollar-sign"></i>
                                        <span>Price Range</span>
                                    </label>
                                    <div class="advanced-filter-price-inputs">
                                        <input type="number" 
                                               name="min_price" 
                                               value="<?= escape($_GET['min_price'] ?? '') ?>" 
                                               placeholder="Min"
                                               min="0"
                                               step="0.01"
                                               class="advanced-filter-input advanced-filter-price-input"
                                               onchange="applyFilters()">
                                        <span class="advanced-filter-price-separator">-</span>
                                        <input type="number" 
                                               name="max_price" 
                                               value="<?= escape($_GET['max_price'] ?? '') ?>" 
                                               placeholder="Max"
                                               min="0"
                                               step="0.01"
                                               class="advanced-filter-input advanced-filter-price-input"
                                               onchange="applyFilters()">
                                    </div>
                                    <div class="advanced-filter-hint">
                                        <i class="fas fa-info-circle"></i>
                                        Range: $<?= number_format($minPriceRange, 2) ?> - $<?= number_format($maxPriceRange, 2) ?>
                                    </div>
                                </div>
                                
                                <!-- Category -->
                                <div class="advanced-filter-item">
                                    <label class="advanced-filter-label">
                                        <i class="fas fa-th-large"></i>
                                        <span>Category</span>
                                    </label>
                                    <select name="category" 
                                            class="advanced-filter-select"
                                            onchange="applyFilters()">
                                        <option value="">All Categories</option>
                                        <?php 
                                        $flatCategories = $categoryModel->getFlatTree(null, true);
                                        foreach ($flatCategories as $cat): 
                                            $indent = str_repeat('&nbsp;&nbsp;', $cat['level'] ?? 0);
                                            $prefix = ($cat['level'] ?? 0) > 0 ? '└─ ' : '';
                                        ?>
                                            <option value="<?= escape($cat['slug']) ?>" 
                                                    <?= ($_GET['category'] ?? '') === $cat['slug'] ? 'selected' : '' ?>>
                                                <?= $indent . $prefix . escape($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Quick Filters -->
                                <div class="advanced-filter-item advanced-filter-quick">
                                    <label class="advanced-filter-label">
                                        <i class="fas fa-bolt"></i>
                                        <span>Quick Filters</span>
                                    </label>
                                    <div class="advanced-filter-chips">
                                        <label class="advanced-filter-chip">
                                            <input type="checkbox" 
                                                   name="in_stock" 
                                                   value="1" 
                                                   <?= !empty($_GET['in_stock']) ? 'checked' : '' ?>
                                                   onchange="applyFilters()">
                                            <span><i class="fas fa-box-check"></i> In Stock</span>
                                        </label>
                                        <label class="advanced-filter-chip advanced-filter-chip-featured">
                                            <input type="checkbox" 
                                                   name="featured" 
                                                   value="1" 
                                                   <?= !empty($_GET['featured']) ? 'checked' : '' ?>
                                                   onchange="applyFilters()">
                                            <span><i class="fas fa-star"></i> Featured</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="advanced-filters-actions">
                                <button type="button" 
                                        onclick="clearFilters()" 
                                        class="advanced-filter-btn advanced-filter-btn-clear">
                                    <i class="fas fa-redo"></i>
                                    <span>Clear All</span>
                                </button>
                                <button type="submit" 
                                        class="advanced-filter-btn advanced-filter-btn-apply hidden"
                                        id="advanced-filter-submit">
                                    <i class="fas fa-check"></i>
                                    <span>Apply</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- App-Style Header -->
                <div class="app-products-header">
                    <div class="app-header-content">
                        <div class="app-header-title-section">
                            <?php if (!empty($categoryName) && !empty($categoryBreadcrumbs)): ?>
                                <nav class="mb-2" aria-label="Breadcrumb">
                                    <ol class="flex items-center space-x-2 text-sm text-gray-600">
                                        <?php foreach ($categoryBreadcrumbs as $idx => $crumb): ?>
                                            <li class="flex items-center">
                                                <?php if ($idx > 0): ?>
                                                    <i class="fas fa-chevron-right mx-2 text-gray-400 text-xs"></i>
                                                <?php endif; ?>
                                                <?php if ($idx < count($categoryBreadcrumbs) - 1): ?>
                                                    <a href="<?= escape($crumb['url']) ?>" class="hover:text-blue-600 transition-colors">
                                                        <?= escape($crumb['name']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-gray-900 font-semibold"><?= escape($crumb['name']) ?></span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ol>
                                </nav>
                            <?php endif; ?>
                            <h1 class="app-page-title flex items-center gap-3">
                                <?php if (!empty($categoryName)): ?>
                                    <span><?= escape($categoryName) ?></span>
                                    <?php if (!empty($subCategories)): ?>
                                        <span class="text-sm text-gray-500 font-normal">(<?= count($subCategories) ?> sub-categories)</span>
                                    <?php endif; ?>
                                    <?php if (session('admin_logged_in') && !empty($category)): ?>
                                        <button onclick="featureCategory('<?= escape($category['slug']) ?>', <?= $category['id'] ?>)" 
                                                class="ml-2 px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors flex items-center gap-1.5"
                                                id="feature-category-btn"
                                                title="Feature all products in this category">
                                            <i class="fas fa-star"></i>
                                            <span>Feature All</span>
                                        </button>
                                        <button onclick="unfeatureCategory('<?= escape($category['slug']) ?>', <?= $category['id'] ?>)" 
                                                class="ml-2 px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors flex items-center gap-1.5"
                                                id="unfeature-category-btn"
                                                title="Unfeature all products in this category">
                                            <i class="far fa-star"></i>
                                            <span>Unfeature All</span>
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    All Products
                                <?php endif; ?>
                            </h1>
                            <?php if (!empty($_GET['search'])): ?>
                                <div class="app-search-badge">
                                    <i class="fas fa-search"></i>
                                    <span><?= escape($_GET['search']) ?></span>
                                </div>
                            <?php endif; ?>
                            <p class="app-results-count">
                                <i class="fas fa-box"></i>
                                <?= $totalProducts ?> <?= $totalProducts === 1 ? 'product' : 'products' ?>
                            </p>
                        </div>
                        
                        <div class="app-header-controls">
                            <!-- Request Catalog Button -->
                            <a href="<?= url('request-catalog.php') ?>" 
                               class="app-btn-secondary mr-3"
                               title="Request PDF Catalog">
                                <i class="fas fa-file-pdf"></i>
                                <span class="hidden sm:inline">Request Catalog</span>
                            </a>
                            <!-- Sort Dropdown - App Style -->
                            <div class="app-sort-container">
                                <label class="app-sort-label">
                                    <i class="fas fa-sort"></i>
                                    <span class="hidden sm:inline">Sort</span>
                                </label>
                                <select id="sort-select" onchange="applyFilters()" class="app-sort-select">
                                    <option value="" <?= empty($_GET['sort']) ? 'selected' : '' ?>>Featured First (Default)</option>
                                    <option value="name" <?= ($_GET['sort'] ?? '') === 'name' ? 'selected' : '' ?>>Name A-Z</option>
                                    <option value="name_desc" <?= ($_GET['sort'] ?? '') === 'name_desc' ? 'selected' : '' ?>>Name Z-A</option>
                                    <option value="price_asc" <?= ($_GET['sort'] ?? '') === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                                    <option value="price_desc" <?= ($_GET['sort'] ?? '') === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                                    <option value="newest" <?= ($_GET['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                    <option value="featured" <?= ($_GET['sort'] ?? '') === 'featured' ? 'selected' : '' ?>>Featured First</option>
                                </select>
                            </div>
                            
                            <!-- Layout Switcher - App Style -->
                            <div class="app-layout-switcher">
                                <button onclick="setLayout('grid')" id="layout-grid" class="app-layout-btn active" title="Grid View">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button onclick="setLayout('list')" id="layout-list" class="app-layout-btn" title="List View">
                                    <i class="fas fa-list"></i>
                                </button>
                                <button onclick="setLayout('compact')" id="layout-compact" class="app-layout-btn" title="Compact View">
                                    <i class="fas fa-th-large"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($products)): ?>
                    <div class="app-empty-state">
                        <div class="app-empty-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <h3 class="app-empty-title">No Products Found</h3>
                        <p class="app-empty-text">Try adjusting your filters or search terms</p>
                        <a href="<?= url('products.php') ?>" class="app-btn-primary">
                            <i class="fas fa-redo mr-2"></i>Reset Filters
                        </a>
                    </div>
                <?php else: ?>
                    <div class="products-container app-products-grid" id="products-grid" data-layout="grid">
                        <?php foreach ($products as $product): ?>
                        <div class="app-product-card product-item" data-product-id="<?= $product['id'] ?>">
                            <a href="<?= url('product.php?slug=' . escape($product['slug'])) ?>" class="app-product-link">
                                <!-- Product Image -->
                                <div class="app-product-image-wrapper">
                                    <?php if (!empty($product['image'])): ?>
                                        <?php
                                        // Ensure image path is correct
                                        $imagePath = $product['image'];
                                        // If image doesn't start with storage/uploads/, add it
                                        if (strpos($imagePath, 'storage/uploads/') !== 0 && strpos($imagePath, '/') !== 0 && !preg_match('/^https?:\/\//', $imagePath)) {
                                            $imagePath = 'storage/uploads/' . ltrim($imagePath, '/');
                                        }
                                        $imageUrl = image_url($imagePath);
                                        ?>
                                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 300'%3E%3Crect fill='%23e5e7eb' width='400' height='300'/%3E%3C/svg%3E" 
                                             data-src="<?= escape($imageUrl) ?>"
                                             alt="<?= escape($product['name']) ?>" 
                                             class="app-product-image lazy-load"
                                             loading="lazy"
                                             onerror="this.onerror=null; this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                                        <div class="app-image-fallback" style="display: none;">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="app-product-placeholder">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Featured Badge -->
                                    <?php if ($product['is_featured']): ?>
                                        <div class="app-featured-badge" title="Featured Product">
                                            <i class="fas fa-star"></i>
                                            <span>Featured</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Quick Actions Overlay -->
                                    <div class="app-product-overlay">
                                        <button onclick="event.preventDefault(); event.stopPropagation(); openQuickView(<?= $product['id'] ?>)" 
                                                class="app-overlay-btn"
                                                title="Quick View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="event.preventDefault(); event.stopPropagation(); addToCart(<?= $product['id'] ?>)" 
                                                class="app-overlay-btn app-overlay-btn-primary"
                                                data-quick-add-cart="<?= $product['id'] ?>"
                                                title="Add to Cart">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                        <button onclick="event.preventDefault(); event.stopPropagation(); addToWishlist(<?= $product['id'] ?>)" 
                                                class="app-overlay-btn app-overlay-btn-wishlist"
                                                id="wishlist-btn-<?= $product['id'] ?>"
                                                title="Add to Wishlist">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                        <button onclick="event.preventDefault(); event.stopPropagation(); addToCompare(<?= $product['id'] ?>)" 
                                                class="app-overlay-btn app-overlay-btn-compare"
                                                id="compare-btn-<?= $product['id'] ?>"
                                                title="Add to Compare">
                                            <i class="fas fa-balance-scale"></i>
                                        </button>
                                        <?php if (session('admin_logged_in')): ?>
                                        <button onclick="event.preventDefault(); event.stopPropagation(); toggleFeatured(<?= $product['id'] ?>, this)" 
                                                class="app-overlay-btn app-overlay-btn-feature <?= $product['is_featured'] ? 'active' : '' ?>"
                                                id="feature-btn-<?= $product['id'] ?>"
                                                title="<?= $product['is_featured'] ? 'Click to Unfeature' : 'Click to Feature' ?>">
                                            <i class="<?= $product['is_featured'] ? 'fas fa-star' : 'far fa-star' ?>"></i>
                                            <?php if ($product['is_featured']): ?>
                                            <span class="feature-btn-text">Unfeature</span>
                                            <?php else: ?>
                                            <span class="feature-btn-text">Feature</span>
                                            <?php endif; ?>
                                        </button>
                                        <?php if ($product['is_featured']): ?>
                                        <button onclick="event.preventDefault(); event.stopPropagation(); openFeaturedOrderDialog(<?= $product['id'] ?>, <?= (int)($product['featured_order'] ?? 0) ?>, '<?= escape($product['name']) ?>')" 
                                                class="app-overlay-btn app-overlay-btn-order"
                                                id="order-btn-<?= $product['id'] ?>"
                                                title="Set Featured Order (current: <?= (int)($product['featured_order'] ?? 0) ?>)">
                                            <i class="fas fa-sort-numeric-down"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Product Info -->
                                <div class="app-product-info">
                                    <div class="app-product-category">
                                        <i class="fas fa-tag"></i>
                                        <?= escape($product['category_name'] ?? 'Uncategorized') ?>
                                    </div>
                                    <h3 class="app-product-title"><?= escape($product['name']) ?></h3>
                                    <?php if (!empty($product['short_description'])): ?>
                                        <p class="app-product-description"><?= escape($product['short_description']) ?></p>
                                    <?php endif; ?>
                                    
                                    <!-- Price Section -->
                                    <div class="app-product-price-section">
                                        <?php 
                                        $price = !empty($product['price']) && $product['price'] > 0 ? (float)$product['price'] : null;
                                        $salePrice = !empty($product['sale_price']) && $product['sale_price'] > 0 ? (float)$product['sale_price'] : null;
                                        ?>
                                        <?php if ($salePrice && $price): ?>
                                            <div class="app-price-container">
                                                <span class="app-price-current">$<?= number_format((float)$salePrice, 2) ?></span>
                                                <span class="app-price-original">$<?= number_format((float)$price, 2) ?></span>
                                            </div>
                                            <div class="app-discount-badge">
                                                <?= round((($price - $salePrice) / $price) * 100) ?>% OFF
                                            </div>
                                        <?php elseif ($price): ?>
                                            <span class="app-price-current">$<?= number_format((float)$price, 2) ?></span>
                                        <?php else: ?>
                                            <span class="app-price-request">Price on Request</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Action Button -->
                                    <button onclick="event.preventDefault(); event.stopPropagation(); window.location.href='<?= url('product.php?slug=' . escape($product['slug'])) ?>'" 
                                            class="app-product-btn">
                                        <span>View Details</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Load More Button -->
                    <?php if ($totalPages > 1 && $filters['page'] < $totalPages): ?>
                    <div class="mt-12 text-center" id="load-more-container">
                        <button id="load-more-btn" 
                                data-current-page="<?= $filters['page'] ?>"
                                data-total-pages="<?= $totalPages ?>"
                                data-category="<?= escape($_GET['category'] ?? '') ?>"
                                data-search="<?= escape($_GET['search'] ?? '') ?>"
                                data-featured="<?= escape($_GET['featured'] ?? '') ?>"
                                class="btn-primary px-8 py-3 text-lg">
                            <i class="fas fa-spinner fa-spin hidden mr-2" id="load-more-spinner"></i>
                            <span id="load-more-text">Load More Products</span>
                            <span class="text-sm font-normal ml-2" id="load-more-count">(<?= $totalProducts - (count($products) * $filters['page']) ?> remaining)</span>
                        </button>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
// Sidebar Toggle Function (Desktop only)
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar-filters-desktop');
    if (!sidebar) return;
    
    const isExpanded = !sidebar.classList.contains('collapsed');
    
    if (isExpanded) {
        // Collapse sidebar
        sidebar.classList.add('collapsed');
        localStorage.setItem('sidebarExpanded', 'false');
    } else {
        // Expand sidebar
        sidebar.classList.remove('collapsed');
        localStorage.setItem('sidebarExpanded', 'true');
    }
}

// Expand sidebar and focus on specific section (Desktop only)
function expandAndFocus(section) {
    const sidebar = document.getElementById('sidebar-filters-desktop');
    if (!sidebar) return;
    
    // Expand sidebar if collapsed
    if (sidebar.classList.contains('collapsed')) {
        sidebar.classList.remove('collapsed');
        localStorage.setItem('sidebarExpanded', 'true');
    }
    
    // Focus on specific section after a short delay
    setTimeout(() => {
        switch(section) {
            case 'search':
                const searchInput = document.querySelector('#sidebar-filters-desktop input[name="search"]');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                break;
            case 'price':
                const priceInput = document.querySelector('#sidebar-filters-desktop input[name="min_price"]');
                if (priceInput) {
                    priceInput.focus();
                    priceInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                break;
            case 'categories':
                const categoryToggle = document.getElementById('category-filter-toggle');
                if (categoryToggle) {
                    // Expand categories if collapsed
                    const categoryContent = document.getElementById('category-filter-content');
                    if (categoryContent && categoryContent.classList.contains('hidden')) {
                        toggleCategoryFilter();
                    }
                    categoryToggle.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                break;
            case 'filters':
                // Just expand, no specific focus
                break;
        }
    }, 100);
}

// Mobile Filter Functions
function openMobileFilters() {
    const mobileSidebar = document.getElementById('sidebar-filters-mobile');
    if (mobileSidebar) {
        mobileSidebar.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        updateMobileFilterCount();
    }
}

function closeMobileFilters() {
    const mobileSidebar = document.getElementById('sidebar-filters-mobile');
    if (mobileSidebar) {
        mobileSidebar.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

function toggleMobileCategoryFilter() {
    const content = document.getElementById('mobile-category-content');
    const chevron = document.getElementById('mobile-category-chevron');
    if (content && chevron) {
        content.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }
}

function applyMobileFilters() {
    document.getElementById('mobile-filter-form').submit();
}

function updateMobileFilterCount() {
    const form = document.getElementById('mobile-filter-form');
    let count = 0;
    
    // Count active filters
    if (form.querySelector('input[name="search"]').value) count++;
    if (form.querySelector('input[name="min_price"]').value || form.querySelector('input[name="max_price"]').value) count++;
    if (form.querySelector('input[name="category"]:checked') && form.querySelector('input[name="category"]:checked').value) count++;
    if (form.querySelector('input[name="in_stock"]:checked')) count++;
    if (form.querySelector('input[name="featured"]:checked')) count++;
    
    const badge = document.getElementById('mobile-filter-count');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    }
}

// Initialize sidebar state (Desktop only)
document.addEventListener('DOMContentLoaded', function() {
    const savedState = localStorage.getItem('sidebarExpanded');
    const desktopSidebar = document.getElementById('sidebar-filters-desktop');
    
    if (desktopSidebar) {
        // Default to expanded on desktop, collapsed on mobile
        if (savedState === 'false') {
            desktopSidebar.classList.add('collapsed');
        }
    }
    
    // Update mobile filter count on load
    updateMobileFilterCount();
    
    // Update count when filters change
    const mobileForm = document.getElementById('mobile-filter-form');
    if (mobileForm) {
        mobileForm.addEventListener('change', updateMobileFilterCount);
    }
    
    // Initialize advanced filters state
    const savedAdvancedState = localStorage.getItem('advancedFiltersExpanded');
    const advancedContent = document.getElementById('advanced-filters-content');
    const advancedBtn = document.getElementById('advanced-filters-toggle-btn');
    const advancedChevron = document.getElementById('advanced-filters-chevron');
    
    if (advancedContent && advancedBtn && advancedChevron) {
        // Check if mobile
        const isMobile = window.innerWidth <= 640;
        
        // On mobile, always start collapsed (ignore saved state)
        if (isMobile) {
            advancedContent.classList.remove('expanded');
            advancedBtn.setAttribute('aria-expanded', 'false');
            advancedChevron.style.transform = 'rotate(0deg)';
            advancedChevron.classList.remove('rotate-180');
        } else {
            // On desktop, respect saved state or default to collapsed
            if (savedAdvancedState === 'true') {
                advancedContent.classList.add('expanded');
                advancedBtn.setAttribute('aria-expanded', 'true');
                advancedChevron.classList.add('rotate-180');
            } else {
                advancedContent.classList.remove('expanded');
                advancedBtn.setAttribute('aria-expanded', 'false');
                advancedChevron.classList.remove('rotate-180');
            }
        }
    }
    
    // Update advanced filters count on load
    updateAdvancedFiltersCount();
    
    // Update count when filters change
    const advancedForm = document.getElementById('advanced-filters-form');
    if (advancedForm) {
        advancedForm.addEventListener('change', updateAdvancedFiltersCount);
        advancedForm.addEventListener('input', updateAdvancedFiltersCount);
    }
});

// Category Filter Toggle
function toggleCategoryFilter() {
    const content = document.getElementById('category-filter-content');
    const chevron = document.getElementById('category-chevron');
    const isExpanded = !content.classList.contains('hidden');
    
    if (isExpanded) {
        content.classList.add('hidden');
        chevron.classList.remove('rotate-180');
        localStorage.setItem('categoryFilterExpanded', 'false');
    } else {
        content.classList.remove('hidden');
        chevron.classList.add('rotate-180');
        localStorage.setItem('categoryFilterExpanded', 'true');
    }
}

// Advanced Filters Toggle
function toggleAdvancedFilters() {
    const content = document.getElementById('advanced-filters-content');
    const btn = document.getElementById('advanced-filters-toggle-btn');
    const chevron = document.getElementById('advanced-filters-chevron');
    
    if (!content || !btn || !chevron) return;
    
    const isExpanded = content.classList.contains('expanded');
    const isMobile = window.innerWidth <= 640;
    
    if (isExpanded) {
        content.classList.remove('expanded');
        btn.setAttribute('aria-expanded', 'false');
        chevron.classList.remove('rotate-180');
        chevron.style.transform = 'rotate(0deg)';
        // Only save state on desktop
        if (!isMobile) {
            localStorage.setItem('advancedFiltersExpanded', 'false');
        }
    } else {
        content.classList.add('expanded');
        btn.setAttribute('aria-expanded', 'true');
        chevron.classList.add('rotate-180');
        chevron.style.transform = 'rotate(180deg)';
        // Only save state on desktop
        if (!isMobile) {
            localStorage.setItem('advancedFiltersExpanded', 'true');
        }
    }
}

// Update Advanced Filters Count
function updateAdvancedFiltersCount() {
    const form = document.getElementById('advanced-filters-form');
    if (!form) return;
    
    let count = 0;
    
    // Check search
    if (form.querySelector('input[name="search"]')?.value.trim()) count++;
    
    // Check price range
    if (form.querySelector('input[name="min_price"]')?.value || 
        form.querySelector('input[name="max_price"]')?.value) count++;
    
    // Check category
    if (form.querySelector('select[name="category"]')?.value) count++;
    
    // Check quick filters
    if (form.querySelector('input[name="in_stock"]:checked')) count++;
    if (form.querySelector('input[name="featured"]:checked')) count++;
    
    const countBadge = document.getElementById('advanced-filters-count');
    if (countBadge) {
        countBadge.textContent = count;
        countBadge.style.display = count > 0 ? 'inline-flex' : 'none';
    }
}

// Initialize category filter state
document.addEventListener('DOMContentLoaded', function() {
    const savedState = localStorage.getItem('categoryFilterExpanded');
    const content = document.getElementById('category-filter-content');
    const chevron = document.getElementById('category-chevron');
    
    if (!content || !chevron) return;
    
    // Default to expanded if a category is selected
    const hasCategory = '<?= !empty($_GET['category']) ? 'true' : 'false' ?>' === 'true';
    
    if (savedState === 'true' || (savedState === null && hasCategory)) {
        content.classList.remove('hidden');
        chevron.classList.add('rotate-180');
    } else if (savedState === 'false') {
        content.classList.add('hidden');
        chevron.classList.remove('rotate-180');
    }
    
    // Ensure sidebar is visible on desktop
    const desktopSidebar = document.getElementById('sidebar-filters-desktop');
    if (desktopSidebar && window.innerWidth >= 641) {
        desktopSidebar.style.display = 'block';
    }
});

// Layout Management - App Style
let currentLayout = localStorage.getItem('productLayout') || 'grid';

// Make setLayout globally accessible - define it first
window.setLayout = function(layout) {
    currentLayout = layout;
    localStorage.setItem('productLayout', layout);
    const container = document.getElementById('products-grid');
    if (!container) return;
    
    const items = container.querySelectorAll('.product-item');
    
    // Update active button immediately (synchronous)
    document.querySelectorAll('.app-layout-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    const activeBtn = document.getElementById('layout-' + layout);
    if (activeBtn) {
        activeBtn.classList.add('active');
    }
    
    // Remove all layout classes immediately (synchronous)
    container.classList.remove('list-view', 'compact-view');
    items.forEach(item => {
        item.classList.remove('list-item', 'compact-item', 'flex');
    });
    
    // Force reflow to ensure classes are removed before adding new ones
    void container.offsetHeight;
    
    // Apply new layout immediately (synchronous)
    if (layout === 'grid') {
        container.classList.remove('list-view', 'compact-view');
        // Grid is default, no additional classes needed
    } else if (layout === 'list') {
        container.classList.add('list-view');
        items.forEach(item => {
            item.classList.add('list-item');
        });
    } else if (layout === 'compact') {
        container.classList.add('compact-view');
        items.forEach(item => item.classList.add('compact-item'));
    }
    
    // Force another reflow to ensure new classes are applied
    void container.offsetHeight;
}

// Initialize layout after function is defined
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setLayout(currentLayout);
    });
} else {
    setLayout(currentLayout);
}

// Filter Management
let filterTimeout;
function debounceFilter() {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
}

// Handle category click - ensures immediate filtering
function handleCategoryClick(event, categorySlug) {
    // Prevent double-triggering if clicking directly on radio
    if (event.target.type === 'radio') {
        return; // Let the radio button's onchange handle it
    }
    
    // Stop event propagation to prevent label's default behavior
    event.stopPropagation();
    
    // Find and check the radio button
    const form = event.currentTarget.closest('form') || document.getElementById('filter-form');
    if (form) {
        const radio = form.querySelector(`input[name="category"][value="${categorySlug}"]`);
        if (radio && !radio.checked) {
            radio.checked = true;
            // Trigger change event to apply filters
            const changeEvent = new Event('change', { bubbles: true });
            radio.dispatchEvent(changeEvent);
        } else if (radio && radio.checked) {
            // If already checked, just apply filters
            applyFilters(event);
        }
    }
}

function applyFilters(event) {
    // Determine which form triggered this
    let targetForm = null;
    
    // Priority 1: Find the form that contains the triggering element
    if (event && event.target) {
        const form = event.target.closest('form');
        if (form && (form.id === 'filter-form' || form.id === 'advanced-filters-form' || form.id === 'mobile-filter-form')) {
            targetForm = form;
        }
    }
    
    // Priority 2: If category was clicked, use sidebar form
    if (!targetForm) {
        const sidebarForm = document.getElementById('filter-form');
        if (sidebarForm) {
            const sidebarCategory = sidebarForm.querySelector('input[name="category"]:checked');
            // If a category is selected in sidebar, use sidebar form
            if (sidebarCategory !== null) {
                targetForm = sidebarForm;
            }
        }
    }
    
    // Priority 3: Check advanced filters form
    if (!targetForm) {
        const advancedForm = document.getElementById('advanced-filters-form');
        if (advancedForm) {
            targetForm = advancedForm;
        }
    }
    
    // Priority 4: Check mobile form
    if (!targetForm) {
        const mobileForm = document.getElementById('mobile-filter-form');
        if (mobileForm) {
            targetForm = mobileForm;
        }
    }
    
    // Priority 5: Fallback to sidebar form
    if (!targetForm) {
        targetForm = document.getElementById('filter-form');
    }
    
    if (targetForm) {
        // Preserve sort parameter from dropdown (apply to all forms)
        const sortSelect = document.getElementById('sort-select');
        if (sortSelect) {
            // Update sort in the target form
            let sortInput = targetForm.querySelector('input[name="sort"]');
            if (sortSelect.value) {
                if (!sortInput) {
                    sortInput = document.createElement('input');
                    sortInput.type = 'hidden';
                    sortInput.name = 'sort';
                    targetForm.appendChild(sortInput);
                }
                sortInput.value = sortSelect.value;
            } else {
                // If sort is empty, remove the input to use default (featured first)
                if (sortInput) {
                    sortInput.remove();
                }
            }
        }
        
        // Reset page to 1 when filters change
        let pageInput = targetForm.querySelector('input[name="page"]');
        if (!pageInput) {
            pageInput = document.createElement('input');
            pageInput.type = 'hidden';
            pageInput.name = 'page';
            targetForm.appendChild(pageInput);
        }
        pageInput.value = '1';
        
        // Submit the form
        targetForm.submit();
    }
}

function clearFilters() {
    window.location.href = '<?= url('products.php') ?>';
}

function quickAddToCart(productId) {
    fetch('<?= url('api/cart.php') ?>?action=add&product_id=' + productId, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show notification if function exists
            if (typeof showNotification === 'function') {
                showNotification('Product added to cart!', 'success');
            } else {
                alert('Product added to cart!');
            }
            // Update cart count
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            } else {
                location.reload();
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding product to cart');
    });
}

// Toggle Featured Status (Admin Only)
// One click to feature, one more click to unfeature
function toggleFeatured(productId, buttonElement) {
    if (!buttonElement) {
        buttonElement = document.getElementById('feature-btn-' + productId);
    }
    
    if (!buttonElement) {
        console.error('Feature button not found for product:', productId);
        return;
    }
    
    // Disable button during request
    buttonElement.disabled = true;
    buttonElement.style.opacity = '0.6';
    
    // Get current state from button class
    const isCurrentlyFeatured = buttonElement.classList.contains('active');
    
    fetch('<?= url('api/toggle-featured.php') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button state
            if (buttonElement) {
                // Update active class
                if (data.is_featured) {
                    buttonElement.classList.add('active');
                } else {
                    buttonElement.classList.remove('active');
                }
                
                // Update title
                buttonElement.title = data.is_featured ? 'Click to Unfeature' : 'Click to Feature';
                
                // Update icon
                const icon = buttonElement.querySelector('i');
                if (icon) {
                    icon.className = data.is_featured ? 'fas fa-star' : 'far fa-star';
                }
                
                // Update button text
                let textSpan = buttonElement.querySelector('.feature-btn-text');
                if (!textSpan) {
                    textSpan = document.createElement('span');
                    textSpan.className = 'feature-btn-text';
                    buttonElement.appendChild(textSpan);
                }
                textSpan.textContent = data.is_featured ? 'Unfeature' : 'Feature';
                
                // Update featured badge and order input on product card
                const productCard = buttonElement.closest('.app-product-card');
                if (productCard) {
                    const imageWrapper = productCard.querySelector('.app-product-image-wrapper');
                    
                    let featuredBadge = productCard.querySelector('.app-featured-badge');
                    if (data.is_featured) {
                        if (!featuredBadge && imageWrapper) {
                            featuredBadge = document.createElement('div');
                            featuredBadge.className = 'app-featured-badge';
                            featuredBadge.title = 'Featured Product';
                            featuredBadge.innerHTML = '<i class="fas fa-star"></i><span>Featured</span>';
                            imageWrapper.appendChild(featuredBadge);
                        }
                        
                        // Get the featured_order from response or default to 0
                        const featuredOrder = data.featured_order !== undefined ? data.featured_order : 0;
                        
                        // Show featured order button if it doesn't exist
                        let orderBtn = productCard.querySelector('.app-overlay-btn-order');
                        if (!orderBtn) {
                            const overlay = productCard.querySelector('.app-product-overlay');
                            if (overlay) {
                                orderBtn = document.createElement('button');
                                orderBtn.className = 'app-overlay-btn app-overlay-btn-order';
                                orderBtn.id = `order-btn-${productId}`;
                                orderBtn.title = `Set Featured Order (current: ${featuredOrder})`;
                                orderBtn.onclick = function(e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    const productName = productCard.querySelector('.app-product-title')?.textContent || 'Product';
                                    openFeaturedOrderDialog(productId, featuredOrder, productName);
                                };
                                orderBtn.innerHTML = '<i class="fas fa-sort-numeric-down"></i>';
                                overlay.appendChild(orderBtn);
                            }
                        } else {
                            // Update existing order button title with new order value
                            orderBtn.title = `Set Featured Order (current: ${featuredOrder})`;
                        }
                    } else {
                        if (featuredBadge) {
                            featuredBadge.remove();
                        }
                        
                        // Hide featured order button
                        const orderBtn = productCard.querySelector('.app-overlay-btn-order');
                        if (orderBtn) {
                            orderBtn.remove();
                        }
                    }
                }
            }
            
            // Show notification
            if (typeof showNotification === 'function') {
                showNotification(data.message, 'success');
            } else {
                alert(data.message);
            }
        } else {
            // Show error
            if (typeof showNotification === 'function') {
                showNotification(data.message || 'Error toggling featured status', 'error');
            } else {
                alert(data.message || 'Error toggling featured status');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showNotification === 'function') {
            showNotification('Error toggling featured status', 'error');
        } else {
            alert('Error toggling featured status');
        }
    })
    .finally(() => {
        // Re-enable button
        if (buttonElement) {
            buttonElement.disabled = false;
            buttonElement.style.opacity = '1';
        }
    });
}

// Load More Products Handler
document.addEventListener('DOMContentLoaded', function() {
    const loadMoreBtn = document.getElementById('load-more-btn');
    const productsGrid = document.getElementById('products-grid');
    
    if (loadMoreBtn && productsGrid) {
        loadMoreBtn.addEventListener('click', function() {
            const currentPage = parseInt(loadMoreBtn.getAttribute('data-current-page')) || 1;
            const nextPage = currentPage + 1;
            const category = loadMoreBtn.getAttribute('data-category') || '';
            const search = loadMoreBtn.getAttribute('data-search') || '';
            const featured = loadMoreBtn.getAttribute('data-featured') || '';
            
            // Show loading state
            const spinner = document.getElementById('load-more-spinner');
            const loadMoreText = document.getElementById('load-more-text');
            if (spinner) spinner.classList.remove('hidden');
            if (loadMoreText) loadMoreText.textContent = 'Loading...';
            loadMoreBtn.disabled = true;
            
            // Build URL with current filters
            const urlParams = new URLSearchParams(window.location.search);
            const params = new URLSearchParams({
                page: nextPage
            });
            
            // Add filters from URL
            if (urlParams.get('category')) params.set('category', urlParams.get('category'));
            if (urlParams.get('search')) params.set('search', urlParams.get('search'));
            if (urlParams.get('featured')) params.set('featured', urlParams.get('featured'));
            if (urlParams.get('min_price')) params.set('min_price', urlParams.get('min_price'));
            if (urlParams.get('max_price')) params.set('max_price', urlParams.get('max_price'));
            if (urlParams.get('in_stock')) params.set('in_stock', urlParams.get('in_stock'));
            if (urlParams.get('sort')) params.set('sort', urlParams.get('sort'));
            
            // Fetch more products
            fetch('<?= url('api/load-more-products.php') ?>?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.html) {
                        // Create temporary container to parse HTML
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = data.html;
                        
                        // Get all product cards from the response
                        const newProducts = tempDiv.querySelectorAll('.app-product-card');
                        
                        // Append each product to the grid
                        newProducts.forEach(product => {
                            productsGrid.appendChild(product);
                        });
                        
                        // Apply current layout to new products
                        const currentLayout = localStorage.getItem('productLayout') || 'grid';
                        if (currentLayout !== 'grid') {
                            newProducts.forEach(item => {
                                if (currentLayout === 'list') {
                                    item.classList.add('list-item');
                                } else if (currentLayout === 'compact') {
                                    item.classList.add('compact-item');
                                }
                            });
                            if (currentLayout === 'list') {
                                productsGrid.classList.add('list-view');
                            } else if (currentLayout === 'compact') {
                                productsGrid.classList.add('compact-view');
                            }
                        }
                        
                        // Initialize lazy loading for new images
                        if (typeof initLazyLoading === 'function') {
                            initLazyLoading();
                        } else {
                            // Fallback: trigger lazy loading manually
                            setTimeout(() => {
                                const lazyImages = tempDiv.querySelectorAll('img.lazy-load[data-src]');
                                lazyImages.forEach(img => {
                                    if (img.dataset.src) {
                                        img.src = img.dataset.src;
                                        img.classList.add('loaded');
                                    }
                                });
                            }, 100);
                        }
                        
                        // Ensure overlay functionality works for newly loaded products
                        // All overlay buttons use inline onclick handlers, so they should work automatically
                        // But we can verify the overlay structure is correct
                        setTimeout(() => {
                            const newProductCards = productsGrid.querySelectorAll('.app-product-card:not([data-overlay-checked])');
                            newProductCards.forEach(card => {
                                // Mark as checked
                                card.setAttribute('data-overlay-checked', 'true');
                                
                                // Verify overlay exists and is properly positioned
                                const imageWrapper = card.querySelector('.app-product-image-wrapper');
                                const overlay = card.querySelector('.app-product-overlay');
                                
                                if (imageWrapper && overlay) {
                                    // Ensure image wrapper has position relative (should be from CSS)
                                    const computedStyle = window.getComputedStyle(imageWrapper);
                                    if (computedStyle.position === 'static') {
                                        imageWrapper.style.position = 'relative';
                                    }
                                    
                                    // Ensure overlay has position absolute (should be from CSS)
                                    const overlayStyle = window.getComputedStyle(overlay);
                                    if (overlayStyle.position !== 'absolute') {
                                        overlay.style.position = 'absolute';
                                        overlay.style.inset = '0';
                                    }
                                }
                            });
                        }, 200);
                        
                        // Update button state
                        loadMoreBtn.setAttribute('data-current-page', nextPage);
                        
                        // Update remaining count
                        const loadMoreCount = document.getElementById('load-more-count');
                        if (loadMoreCount) {
                            const remaining = data.totalProducts - (nextPage * 12);
                            if (remaining > 0) {
                                loadMoreCount.textContent = `(${remaining} remaining)`;
                            } else {
                                loadMoreCount.textContent = '';
                            }
                        }
                        
                        // Hide button if no more products
                        if (!data.hasMore) {
                            const loadMoreContainer = document.getElementById('load-more-container');
                            if (loadMoreContainer) {
                                loadMoreContainer.style.display = 'none';
                            }
                        }
                    } else {
                        console.error('Failed to load more products');
                        if (typeof showNotification === 'function') {
                            showNotification('Failed to load more products', 'error');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading more products:', error);
                    if (typeof showNotification === 'function') {
                        showNotification('Error loading more products', 'error');
                    }
                })
                .finally(() => {
                    // Reset loading state
                    if (spinner) spinner.classList.add('hidden');
                    if (loadMoreText) loadMoreText.textContent = 'Load More Products';
                    loadMoreBtn.disabled = false;
                });
        });
    }
});

// Feature All Products in Category
function featureCategory(categorySlug, categoryId) {
    const btn = document.getElementById('feature-category-btn') || document.getElementById('feature-category-btn-admin');
    
    if (!btn) {
        console.error('Feature category button not found');
        return;
    }
    
    // Confirm action
    const categoryName = btn.getAttribute('title')?.replace("Feature all products in '", '').replace("' category", '') || 'this category';
    if (!confirm(`Are you sure you want to feature ALL products in "${categoryName}"?\n\nThis will mark all products in this category (including subcategories) as featured.`)) {
        return;
    }
    
    // Disable button during request
    btn.disabled = true;
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Processing...</span>';
    btn.style.opacity = '0.7';
    
    // Prepare request data
    const formData = new URLSearchParams();
    if (categorySlug) {
        formData.append('category_slug', categorySlug);
    }
    if (categoryId) {
        formData.append('category_id', categoryId);
    }
    
    fetch('<?= url('api/feature-category.php') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            if (typeof showNotification === 'function') {
                showNotification(data.message, 'success');
            } else {
                alert(data.message);
            }
            
            // Update all product cards on the page to show featured status
            const productCards = document.querySelectorAll('.app-product-card');
            productCards.forEach(card => {
                // Update featured badge
                let featuredBadge = card.querySelector('.app-featured-badge');
                if (!featuredBadge) {
                    const imageWrapper = card.querySelector('.app-product-image-wrapper');
                    if (imageWrapper) {
                        featuredBadge = document.createElement('div');
                        featuredBadge.className = 'app-featured-badge';
                        featuredBadge.title = 'Featured Product';
                        featuredBadge.innerHTML = '<i class="fas fa-star"></i><span>Featured</span>';
                        imageWrapper.appendChild(featuredBadge);
                    }
                }
                
                // Update feature button
                const featureBtn = card.querySelector('.app-overlay-btn-feature');
                if (featureBtn) {
                    featureBtn.classList.add('active');
                    featureBtn.title = 'Remove from Featured';
                    const icon = featureBtn.querySelector('i');
                    if (icon) {
                        icon.className = 'fas fa-star';
                    }
                }
            });
            
            // Update button text
            btn.innerHTML = '<i class="fas fa-check"></i> <span>All Featured!</span>';
            btn.style.background = '#10b981';
            btn.style.opacity = '1';
            
            // Reset button after 3 seconds
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.style.background = '';
                btn.disabled = false;
            }, 3000);
        } else {
            // Show error
            if (typeof showNotification === 'function') {
                showNotification(data.message || 'Error featuring category products', 'error');
            } else {
                alert(data.message || 'Error featuring category products');
            }
            
            // Reset button
            btn.innerHTML = originalHTML;
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showNotification === 'function') {
            showNotification('Error featuring category products', 'error');
        } else {
            alert('Error featuring category products');
        }
        
        // Reset button
        btn.innerHTML = originalHTML;
        btn.disabled = false;
        btn.style.opacity = '1';
    });
}

// Open Featured Order Dialog
function openFeaturedOrderDialog(productId, currentOrder, productName) {
    // Create modal overlay
    const modal = document.createElement('div');
    modal.className = 'featured-order-modal-overlay';
    modal.innerHTML = `
        <div class="featured-order-modal" onclick="event.stopPropagation();">
            <div class="featured-order-modal-header">
                <h3 class="featured-order-modal-title">
                    <i class="fas fa-sort-numeric-down"></i>
                    Set Featured Order
                </h3>
                <button onclick="closeFeaturedOrderDialog()" class="featured-order-modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="featured-order-modal-body">
                <p class="featured-order-product-name">
                    <i class="fas fa-box"></i>
                    <strong>${productName}</strong>
                </p>
                <div class="featured-order-input-group">
                    <label class="featured-order-label">
                        <i class="fas fa-hashtag"></i>
                        Featured Order Number
                    </label>
                    <input type="number" 
                           id="featured-order-input-${productId}"
                           value="${currentOrder}" 
                           min="0" 
                           step="1"
                           class="featured-order-input-field"
                           placeholder="0 (lower numbers appear first)">
                    <p class="featured-order-hint">
                        <i class="fas fa-info-circle"></i>
                        Lower numbers appear first. Set to 0 for default order.
                    </p>
                </div>
            </div>
            <div class="featured-order-modal-footer">
                <button onclick="closeFeaturedOrderDialog()" class="featured-order-btn-cancel">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <button onclick="saveFeaturedOrder(${productId})" class="featured-order-btn-save">
                    <i class="fas fa-check"></i>
                    Save Order
                </button>
            </div>
        </div>
    `;
    
    // Add to body
    document.body.appendChild(modal);
    
    // Focus input
    setTimeout(() => {
        const input = document.getElementById(`featured-order-input-${productId}`);
        if (input) {
            input.focus();
            input.select();
        }
    }, 100);
    
    // Close on overlay click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeFeaturedOrderDialog();
        }
    });
    
    // Close on Escape key
    const escapeHandler = function(e) {
        if (e.key === 'Escape') {
            closeFeaturedOrderDialog();
            document.removeEventListener('keydown', escapeHandler);
        }
    };
    document.addEventListener('keydown', escapeHandler);
    
    // Store handler for cleanup
    modal.dataset.escapeHandler = 'true';
}

// Close Featured Order Dialog
function closeFeaturedOrderDialog() {
    const modal = document.querySelector('.featured-order-modal-overlay');
    if (modal) {
        modal.remove();
    }
}

// Save Featured Order
function saveFeaturedOrder(productId) {
    const input = document.getElementById(`featured-order-input-${productId}`);
    if (!input) {
        closeFeaturedOrderDialog();
        return;
    }
    
    const orderValue = parseInt(input.value) || 0;
    if (orderValue < 0) {
        if (typeof showNotification === 'function') {
            showNotification('Order must be 0 or greater', 'error');
        } else {
            alert('Order must be 0 or greater');
        }
        return;
    }
    
    // Close dialog first
    closeFeaturedOrderDialog();
    
    // Update order
    updateFeaturedOrder(productId, orderValue);
}

// Update Featured Order (enhanced version)
function updateFeaturedOrder(productId, orderValue) {
    // Validate order value
    const order = parseInt(orderValue) || 0;
    if (order < 0) {
        order = 0;
    }
    
    // Show loading state
    const orderBtn = document.getElementById('order-btn-' + productId);
    if (orderBtn) {
        orderBtn.disabled = true;
        orderBtn.style.opacity = '0.6';
    }
    
    fetch('<?= url('api/update-featured-order.php') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&featured_order=${order}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            if (typeof showNotification === 'function') {
                showNotification(`Featured order updated to ${data.featured_order}`, 'success');
            } else {
                alert(`Featured order updated to ${data.featured_order}`);
            }
            
            // Update button title
            if (orderBtn) {
                orderBtn.title = `Set Featured Order (current: ${data.featured_order})`;
            }
            
            // Note: Products will reorder on next page load/filter
            // Optionally, we could reload the page to see the new order immediately
        } else {
            // Show error
            if (typeof showNotification === 'function') {
                showNotification(data.message || 'Error updating featured order', 'error');
            } else {
                alert(data.message || 'Error updating featured order');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showNotification === 'function') {
            showNotification('Error updating featured order', 'error');
        } else {
            alert('Error updating featured order');
        }
    })
    .finally(() => {
        // Re-enable button
        if (orderBtn) {
            orderBtn.disabled = false;
            orderBtn.style.opacity = '1';
        }
    });
}

// Unfeature All Products in Category
function unfeatureCategory(categorySlug, categoryId) {
    const btn = document.getElementById('unfeature-category-btn') || document.getElementById('unfeature-category-btn-admin');
    
    if (!btn) {
        console.error('Unfeature category button not found');
        return;
    }
    
    // Confirm action
    const categoryName = btn.getAttribute('title')?.replace("Unfeature all products in '", '').replace("' category", '') || 'this category';
    if (!confirm(`Are you sure you want to UNFEATURE ALL products in "${categoryName}"?\n\nThis will remove featured status from all products in this category (including subcategories).`)) {
        return;
    }
    
    // Disable button during request
    btn.disabled = true;
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Processing...</span>';
    btn.style.opacity = '0.7';
    
    // Prepare request data
    const formData = new URLSearchParams();
    if (categorySlug) {
        formData.append('category_slug', categorySlug);
    }
    if (categoryId) {
        formData.append('category_id', categoryId);
    }
    
    fetch('<?= url('api/unfeature-category.php') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            if (typeof showNotification === 'function') {
                showNotification(data.message, 'success');
            } else {
                alert(data.message);
            }
            
            // Update all product cards on the page to remove featured status
            const productCards = document.querySelectorAll('.app-product-card');
            productCards.forEach(card => {
                // Remove featured badge
                const featuredBadge = card.querySelector('.app-featured-badge');
                if (featuredBadge) {
                    featuredBadge.remove();
                }
                
                // Update feature button
                const featureBtn = card.querySelector('.app-overlay-btn-feature');
                if (featureBtn) {
                    featureBtn.classList.remove('active');
                    featureBtn.title = 'Click to Feature';
                    const icon = featureBtn.querySelector('i');
                    if (icon) {
                        icon.className = 'far fa-star';
                    }
                    const textSpan = featureBtn.querySelector('.feature-btn-text');
                    if (textSpan) {
                        textSpan.textContent = 'Feature';
                    } else {
                        // Create text span if it doesn't exist
                        const newTextSpan = document.createElement('span');
                        newTextSpan.className = 'feature-btn-text';
                        newTextSpan.textContent = 'Feature';
                        featureBtn.appendChild(newTextSpan);
                    }
                }
                
                // Remove order button
                const orderBtn = card.querySelector('.app-overlay-btn-order');
                if (orderBtn) {
                    orderBtn.remove();
                }
            });
            
            // Update button text
            btn.innerHTML = '<i class="fas fa-check"></i> <span>All Unfeatured!</span>';
            btn.style.background = '#10b981';
            btn.style.opacity = '1';
            
            // Reset button after 3 seconds
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.style.background = '';
                btn.disabled = false;
            }, 3000);
        } else {
            // Show error
            if (typeof showNotification === 'function') {
                showNotification(data.message || 'Error unfeaturing category products', 'error');
            } else {
                alert(data.message || 'Error unfeaturing category products');
            }
            
            // Reset button
            btn.innerHTML = originalHTML;
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showNotification === 'function') {
            showNotification('Error unfeaturing category products', 'error');
        } else {
            alert('Error unfeaturing category products');
        }
        
        // Reset button
        btn.innerHTML = originalHTML;
        btn.disabled = false;
        btn.style.opacity = '1';
    });
}
</script>

<style>
/* Featured Order Modal Styles */
.featured-order-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.featured-order-modal {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    width: 90%;
    max-width: 450px;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.featured-order-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.featured-order-modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.featured-order-modal-title i {
    color: #8b5cf6;
}

.featured-order-modal-close {
    width: 32px;
    height: 32px;
    border: none;
    background: #f3f4f6;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    color: #6b7280;
}

.featured-order-modal-close:hover {
    background: #e5e7eb;
    color: #1f2937;
}

.featured-order-modal-body {
    padding: 1.5rem;
}

.featured-order-product-name {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 1.25rem;
    color: #4b5563;
    font-size: 0.875rem;
}

.featured-order-product-name i {
    color: #8b5cf6;
}

.featured-order-input-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.featured-order-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.featured-order-label i {
    color: #8b5cf6;
}

.featured-order-input-field {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.2s;
    -moz-appearance: textfield;
}

.featured-order-input-field::-webkit-outer-spin-button,
.featured-order-input-field::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.featured-order-input-field:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.featured-order-hint {
    font-size: 0.75rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.375rem;
    margin-top: 0.25rem;
}

.featured-order-hint i {
    color: #9ca3af;
}

.featured-order-modal-footer {
    display: flex;
    gap: 0.75rem;
    padding: 1.5rem;
    border-top: 1px solid #e5e7eb;
    justify-content: flex-end;
}

.featured-order-btn-cancel,
.featured-order-btn-save {
    padding: 0.625rem 1.25rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.featured-order-btn-cancel {
    background: #f3f4f6;
    color: #6b7280;
}

.featured-order-btn-cancel:hover {
    background: #e5e7eb;
    color: #374151;
}

.featured-order-btn-save {
    background: #8b5cf6;
    color: white;
}

.featured-order-btn-save:hover {
    background: #7c3aed;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>

