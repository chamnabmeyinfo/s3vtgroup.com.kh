<?php
// AGGRESSIVE CACHE PREVENTION - Force fresh data on every request
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: 0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('ETag: "' . md5(time() . rand()) . '"');

// Prevent OPcache from serving cached version
if (function_exists('opcache_reset')) {
    // Note: opcache_reset() clears ALL cached files, use carefully in production
    // For single file, we'll use version parameter instead
}

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Product;
use App\Models\Category;

$productModel = new Product();
$categoryModel = new Category();

$message = '';
$error = '';

// Handle delete
if (!empty($_GET['delete'])) {
    try {
        $productId = (int)$_GET['delete'];
        
        // Validate ID
        if ($productId <= 0) {
            $error = 'Invalid product ID.';
        } else {
            // Check if product exists
            $product = $productModel->getById($productId);
            if (!$product) {
                $error = 'Product not found.';
            } else {
                // Perform delete
                $productModel->delete($productId);
                $message = 'Product deleted successfully.';
            }
        }
    } catch (\Exception $e) {
        $error = 'Error deleting product: ' . $e->getMessage();
    }
}

// Handle toggle featured
if (!empty($_GET['toggle_featured'])) {
    $product = $productModel->getById($_GET['toggle_featured']);
    if ($product) {
        $productModel->update($_GET['toggle_featured'], [
            'is_featured' => $product['is_featured'] ? 0 : 1
        ]);
        $message = 'Product updated successfully.';
    }
}

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$featuredFilter = $_GET['featured'] ?? '';
$sort = $_GET['sort'] ?? 'name_asc';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$priceMin = !empty($_GET['price_min']) ? (float)$_GET['price_min'] : null;
$priceMax = !empty($_GET['price_max']) ? (float)$_GET['price_max'] : null;

// Build filter conditions
// IMPORTANT: include_inactive = true means show ALL products (active and inactive)
// Only filter by active status if user explicitly selects a status filter
$filterParams = ['include_inactive' => true];
if ($search) {
    $filterParams['search'] = $search;
}
if ($categoryFilter) {
    $cat = $categoryModel->getBySlug($categoryFilter);
    if ($cat) {
        $filterParams['category_id'] = $cat['id'];
    }
}
// Only apply is_active filter if user explicitly selected a status filter
// Otherwise, show all products (active and inactive)
if ($statusFilter === 'active') {
    $filterParams['is_active'] = 1;
    // Remove include_inactive when filtering by active status
    unset($filterParams['include_inactive']);
} elseif ($statusFilter === 'inactive') {
    $filterParams['is_active'] = 0;
    // Remove include_inactive when filtering by inactive status
    unset($filterParams['include_inactive']);
}
// If no status filter, keep include_inactive = true to show ALL products
if ($featuredFilter === 'yes') {
    $filterParams['is_featured'] = 1;
}

// Get accurate statistics from database - COUNT ALL PRODUCTS (not filtered/paginated)
try {
    // Total products (all products in database)
    $totalProductsResult = db()->fetchOne("SELECT COUNT(*) as count FROM products");
    $totalProducts = (int)($totalProductsResult['count'] ?? 0);
    
    // Active products
    $activeProductsResult = db()->fetchOne("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
    $activeProducts = (int)($activeProductsResult['count'] ?? 0);
    
    // Inactive products
    $inactiveProducts = $totalProducts - $activeProducts;
    
    // Featured products
    $featuredProductsResult = db()->fetchOne("SELECT COUNT(*) as count FROM products WHERE is_featured = 1");
    $featuredProducts = (int)($featuredProductsResult['count'] ?? 0);
    
    // Low stock products (stock_quantity < 10 and > 0)
    try {
        $lowStockResult = db()->fetchOne("SELECT COUNT(*) as count FROM products WHERE stock_quantity < 10 AND stock_quantity > 0");
        $lowStockProducts = (int)($lowStockResult['count'] ?? 0);
    } catch (Exception $e) {
        $lowStockProducts = 0;
    }
    
} catch (Exception $e) {
    // Fallback if query fails - try to get from all products
    try {
        $allProductsForCount = $productModel->getAll(['include_inactive' => true, 'limit' => 999999]);
        $totalProducts = count($allProductsForCount);
        $activeProducts = count(array_filter($allProductsForCount, fn($p) => $p['is_active'] == 1));
        $inactiveProducts = $totalProducts - $activeProducts;
        $featuredProducts = count(array_filter($allProductsForCount, fn($p) => $p['is_featured'] == 1));
        $lowStockProducts = count(array_filter($allProductsForCount, function($p) {
            return (isset($p['stock_quantity']) && $p['stock_quantity'] < 10 && $p['stock_quantity'] > 0);
        }));
    } catch (Exception $e2) {
        $totalProducts = 0;
        $activeProducts = 0;
        $inactiveProducts = 0;
        $featuredProducts = 0;
        $lowStockProducts = 0;
    }
}

// Pagination settings
$page = (int)($_GET['page'] ?? 1);
$limit = 50; // Products per page (increased for better visibility)
$filterParams['page'] = $page;
$filterParams['limit'] = $limit;

// Map sort parameter for Product model
$sortMap = [
    'name_asc' => 'name',
    'name_desc' => 'name_desc',
    'price_asc' => 'price_asc',
    'price_desc' => 'price_desc',
    'date_desc' => 'newest',
    'date_asc' => 'newest'
];
$filterParams['sort'] = $sortMap[$sort] ?? 'name';

// Add price and date filters to filterParams for SQL query (more efficient)
if ($priceMin !== null) {
    $filterParams['min_price'] = $priceMin;
}
if ($priceMax !== null) {
    $filterParams['max_price'] = $priceMax;
}

// Get products with pagination
$products = $productModel->getAll($filterParams);

// Apply date filters that can't be done efficiently in SQL (if needed)
// Note: Price filters are now handled in SQL for better performance
if ($dateFrom || $dateTo) {
    $products = array_filter($products, function($p) use ($dateFrom, $dateTo) {
        $createdAt = strtotime($p['created_at']);
        if ($dateFrom && $createdAt < strtotime($dateFrom)) return false;
        if ($dateTo && $createdAt > strtotime($dateTo . ' 23:59:59')) return false;
        return true;
    });
    $products = array_values($products); // Re-index array
}

// Calculate total count for filtered results (for pagination and display)
// This is calculated early so we can use it in the stats bar
$totalCount = $totalProducts; // Default to all products
try {
    $where = [];
    $params = [];
    
    if ($search) {
        $searchTerm = "%{$search}%";
        $where[] = "(p.name LIKE :search_name OR p.description LIKE :search_desc OR p.short_description LIKE :search_short)";
        $params['search_name'] = $searchTerm;
        $params['search_desc'] = $searchTerm;
        $params['search_short'] = $searchTerm;
    }
    
    if ($categoryFilter) {
        $cat = $categoryModel->getBySlug($categoryFilter);
        if ($cat) {
            $where[] = "p.category_id = :category_id";
            $params['category_id'] = $cat['id'];
        }
    }
    
    if ($statusFilter === 'active') {
        $where[] = "p.is_active = 1";
    } elseif ($statusFilter === 'inactive') {
        $where[] = "p.is_active = 0";
    }
    
    if ($featuredFilter === 'yes') {
        $where[] = "p.is_featured = 1";
    } elseif ($featuredFilter === 'no') {
        $where[] = "p.is_featured = 0";
    }
    
    if ($priceMin !== null) {
        $where[] = "COALESCE(p.sale_price, p.price, 0) >= :min_price";
        $params['min_price'] = $priceMin;
    }
    
    if ($priceMax !== null) {
        $where[] = "COALESCE(p.sale_price, p.price, 0) <= :max_price";
        $params['max_price'] = $priceMax;
    }
    
    if ($dateFrom) {
        $where[] = "DATE(p.created_at) >= :date_from";
        $params['date_from'] = $dateFrom;
    }
    
    if ($dateTo) {
        $where[] = "DATE(p.created_at) <= :date_to";
        $params['date_to'] = $dateTo;
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $countResult = db()->fetchOne("SELECT COUNT(*) as count FROM products p $whereClause", $params);
    $totalCount = (int)($countResult['count'] ?? $totalProducts);
} catch (Exception $e) {
    // Fallback to total products if count query fails
    $totalCount = $totalProducts;
}

// Get all categories for filter
$categories = $categoryModel->getAll();

// Get variant counts for products
$variantCounts = [];
try {
    $variantData = db()->fetchAll(
        "SELECT product_id, COUNT(*) as count FROM product_variants WHERE is_active = 1 GROUP BY product_id"
    );
    foreach ($variantData as $v) {
        $variantCounts[$v['product_id']] = $v['count'];
    }
} catch (Exception $e) {
    // Variants table might not exist
}

// Column visibility - Enable all columns by default
$availableColumns = [
    'checkbox' => 'Checkbox',
    'image' => 'Image',
    'name' => 'Product Name',
    'sku' => 'SKU',
    'category' => 'Category',
    'price' => 'Price',
    'sale_price' => 'Sale Price',
    'stock' => 'Stock Status',
    'views' => 'Views',
    'status' => 'Status',
    'featured' => 'Featured',
    'created' => 'Created Date',
    'actions' => 'Actions'
];

// Default to all columns if no columns specified
// If columns parameter is empty or not set, show all columns by default
$selectedColumns = !empty($_GET['columns']) && is_array($_GET['columns']) ? $_GET['columns'] : array_keys($availableColumns);

// Statistics are now calculated from database queries above (more accurate)

$miniStats = [
    [
        'label' => 'Total Products',
        'value' => number_format($totalProducts),
        'icon' => 'fas fa-box',
        'color' => 'from-blue-500 to-blue-600',
        'description' => 'All products in catalog',
        'link' => url('admin/products.php')
    ],
    [
        'label' => 'Active Products',
        'value' => number_format($activeProducts),
        'icon' => 'fas fa-check-circle',
        'color' => 'from-green-500 to-emerald-600',
        'description' => 'Currently active',
        'link' => url('admin/products.php?status=active')
    ],
    [
        'label' => 'Featured Products',
        'value' => number_format($featuredProducts),
        'icon' => 'fas fa-star',
        'color' => 'from-yellow-500 to-amber-600',
        'description' => 'Featured items',
        'link' => url('admin/products.php?featured=yes')
    ],
    [
        'label' => 'Low Stock',
        'value' => number_format($lowStockProducts),
        'icon' => 'fas fa-exclamation-triangle',
        'color' => 'from-red-500 to-pink-600',
        'description' => 'Need restocking',
        'link' => url('admin/products.php')
    ]
];

$pageTitle = 'Products';
// Add version parameter to prevent caching
$cacheVersion = time();
include __DIR__ . '/includes/header.php';

// Setup filter component variables
$filterId = 'products-filter';
$filters = [
    'search' => true,
    'category' => [
        'options' => array_combine(
            array_column($categories, 'slug'),
            array_column($categories, 'name')
        )
    ],
    'status' => [
        'options' => [
            'all' => 'All Statuses',
            'active' => 'Active Only',
            'inactive' => 'Inactive Only'
        ]
    ],
    'featured' => [
        'options' => [
            'all' => 'All Products',
            'yes' => 'Featured Only',
            'no' => 'Not Featured'
        ]
    ],
    'date_range' => true,
    'price_range' => true
];
$sortOptions = [
    'name_asc' => 'Name (A-Z)',
    'name_desc' => 'Name (Z-A)',
    'price_asc' => 'Price (Low to High)',
    'price_desc' => 'Price (High to Low)',
    'date_desc' => 'Newest First',
    'date_asc' => 'Oldest First'
];
// Default columns - all columns enabled by default
$defaultColumns = array_keys($availableColumns);
?>

<div class="w-full space-y-6">
    <!-- Modern Header with Glassmorphism Effect -->
    <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-blue-600 to-purple-600 rounded-2xl shadow-2xl">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>
        
        <div class="relative p-6 md:p-8 lg:p-10 text-white">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                            <i class="fas fa-box text-2xl md:text-3xl"></i>
                        </div>
            <div>
                            <h1 class="text-3xl md:text-4xl font-bold mb-2">
                    Products Management
                </h1>
                            <p class="text-blue-100 text-base md:text-lg">Manage and organize your product catalog efficiently</p>
            </div>
                    </div>
                    
                    <!-- Quick Stats in Header -->
                    <div class="flex flex-wrap items-center gap-4 mt-4 pt-4 border-t border-white/20">
                        <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-3 py-1.5 rounded-lg">
                            <i class="fas fa-cube text-sm"></i>
                            <span class="text-sm font-medium"><?= number_format($totalProducts) ?> Total</span>
                        </div>
                        <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-3 py-1.5 rounded-lg">
                            <i class="fas fa-check-circle text-sm"></i>
                            <span class="text-sm font-medium"><?= number_format($activeProducts) ?> Active</span>
                        </div>
                        <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-3 py-1.5 rounded-lg">
                            <i class="fas fa-star text-sm"></i>
                            <span class="text-sm font-medium"><?= number_format($featuredProducts) ?> Featured</span>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
                    <a href="<?= url('admin/send-catalog.php') ?>" 
                       class="group bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20 px-5 py-3 rounded-xl transition-all duration-300 text-center font-medium hover:scale-105 hover:shadow-lg">
                        <i class="fas fa-file-pdf mr-2 group-hover:scale-110 transition-transform"></i>
                    Send Catalog
                </a>
                    <a href="<?= url('admin/products-export.php') ?>" 
                       class="group bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20 px-5 py-3 rounded-xl transition-all duration-300 text-center font-medium hover:scale-105 hover:shadow-lg">
                        <i class="fas fa-download mr-2 group-hover:scale-110 transition-transform"></i>
                    Export CSV
                </a>
                    <a href="<?= url('admin/product-edit.php') ?>" 
                       class="group bg-white text-indigo-600 hover:bg-indigo-50 px-6 py-3 rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-2xl text-center hover:scale-105 transform">
                        <i class="fas fa-plus mr-2 group-hover:rotate-90 transition-transform"></i>
                    Add New Product
                </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mini Dashboard Stats -->
    <?php 
    $stats = $miniStats;
    include __DIR__ . '/includes/mini-stats.php'; 
    ?>

    <?php if (!empty($message)): ?>
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 text-green-800 p-4 rounded-lg shadow-md mb-6 animate-slide-in">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-green-500 rounded-full">
                <i class="fas fa-check-circle text-white"></i>
            </div>
            <span class="font-semibold"><?= escape($message) ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    <div class="bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-500 text-red-800 p-4 rounded-lg shadow-md mb-6 animate-slide-in">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-red-500 rounded-full">
                <i class="fas fa-exclamation-circle text-white"></i>
            </div>
            <span class="font-semibold"><?= escape($error) ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Advanced Filters -->
    <?php include __DIR__ . '/includes/advanced-filters.php'; ?>
    
    <!-- Additional Price Range Filter -->
    <?php if (isset($filters['price_range'])): ?>
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-2">Price Min</label>
                <input type="number" name="price_min" value="<?= escape($_GET['price_min'] ?? '') ?>"
                       step="0.01" placeholder="0.00"
                       class="w-full px-4 py-2 border rounded-lg"
                       form="filter-form-<?= $filterId ?>">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Price Max</label>
                <input type="number" name="price_max" value="<?= escape($_GET['price_max'] ?? '') ?>"
                       step="0.01" placeholder="10000.00"
                       class="w-full px-4 py-2 border rounded-lg"
                       form="filter-form-<?= $filterId ?>"
                       onchange="applyFilters('<?= $filterId ?>')">
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Modern Stats Bar with Better Design -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-5">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <!-- Stats Display -->
            <div class="flex flex-wrap items-center gap-4 lg:gap-6">
                <div class="flex items-center gap-2">
                    <div class="p-2 bg-blue-50 rounded-lg">
                        <i class="fas fa-cube text-blue-600"></i>
                    </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Total</div>
                    <div class="text-lg font-bold text-gray-900" id="totalProductsCount"><?= number_format($totalProducts) ?></div>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <div class="p-2 bg-green-50 rounded-lg">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Active</div>
                    <div class="text-lg font-bold text-green-600"><?= number_format($activeProducts) ?></div>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <div class="p-2 bg-yellow-50 rounded-lg">
                    <i class="fas fa-star text-yellow-600"></i>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Featured</div>
                    <div class="text-lg font-bold text-yellow-600"><?= number_format($featuredProducts) ?></div>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <div class="p-2 bg-red-50 rounded-lg">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Low Stock</div>
                    <div class="text-lg font-bold text-red-600"><?= number_format($lowStockProducts) ?></div>
                </div>
            </div>
            
            <div class="hidden md:flex items-center gap-2 pl-4 border-l border-gray-200">
                <div class="p-2 bg-indigo-50 rounded-lg">
                    <i class="fas fa-eye text-indigo-600"></i>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Showing</div>
                    <div class="text-lg font-bold text-indigo-600">
                        <span id="showingCount"><?= count($products) ?></span>
                        <span class="text-sm font-normal text-gray-500">/ <?= number_format($totalCount) ?></span>
                </div>
                </div>
            </div>
                
                <?php if ($search || $categoryFilter || $statusFilter || $featuredFilter || $dateFrom || $dateTo || $priceMin !== null || $priceMax !== null): ?>
                <div class="flex items-center gap-2 bg-indigo-50 border border-indigo-200 px-4 py-2 rounded-lg">
                    <i class="fas fa-filter text-indigo-600"></i>
                    <span class="text-sm font-medium text-indigo-700">Filters Active</span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Bulk Actions -->
            <div id="bulkActions" class="hidden flex items-center gap-3 bg-indigo-50 border border-indigo-200 rounded-lg p-3">
                <select id="bulkActionSelect" class="px-4 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                <option value="">Bulk Actions</option>
                <option value="activate">Activate</option>
                <option value="deactivate">Deactivate</option>
                <option value="feature">Mark as Featured</option>
                <option value="unfeature">Unmark as Featured</option>
                <option value="delete">Delete</option>
            </select>
                <button onclick="executeBulkAction()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg font-medium transition-colors">
                    Apply
                </button>
                <button onclick="clearSelection()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2 rounded-lg font-medium transition-colors">
                    Clear
                </button>
            </div>
        </div>
    </div>
    
    <!-- Products Table with Modern Design -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="product-table-header">
                <tr>
                    <th class="product-table-th product-table-th-checkbox" data-column="checkbox" style="display: <?= (in_array('checkbox', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                        <div class="product-table-th-content">
                            <input type="checkbox" id="selectAll" onchange="toggleAll(this)" class="product-table-checkbox">
                        </div>
                    </th>
                    <th class="product-table-th" data-column="image" data-label="Image" style="display: <?= (in_array('image', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                        <div class="product-table-th-content">
                            <span class="product-table-th-icon"><i class="fas fa-image"></i></span>
                            <span class="product-table-th-text">Image</span>
                        </div>
                    </th>
                    <th class="product-table-th" data-column="name" data-label="Product Name" style="display: <?= (in_array('name', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                        <div class="product-table-th-content">
                            <span class="product-table-th-icon"><i class="fas fa-tag"></i></span>
                            <span class="product-table-th-text">Product Name</span>
                        </div>
                    </th>
                    <th class="product-table-th" data-column="sku" data-label="SKU" style="display: <?= (in_array('sku', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                        <div class="product-table-th-content">
                            <span class="product-table-th-icon"><i class="fas fa-barcode"></i></span>
                            <span class="product-table-th-text">SKU</span>
                        </div>
                    </th>
                    <th class="product-table-th" data-column="category" data-label="Category" style="display: <?= (in_array('category', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                        <div class="product-table-th-content">
                            <span class="product-table-th-icon"><i class="fas fa-folder"></i></span>
                            <span class="product-table-th-text">Category</span>
                        </div>
                    </th>
                    <th class="product-table-th product-table-th-number" data-column="price" data-label="Price" style="display: <?= (in_array('price', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                        <div class="product-table-th-content">
                            <span class="product-table-th-icon"><i class="fas fa-dollar-sign"></i></span>
                            <span class="product-table-th-text">Price</span>
                        </div>
                    </th>
                    <th class="product-table-th product-table-th-number" data-column="sale_price" data-label="Sale Price" style="display: <?= (in_array('sale_price', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                        <div class="product-table-th-content">
                            <span class="product-table-th-icon"><i class="fas fa-tag"></i></span>
                            <span class="product-table-th-text">Sale Price</span>
                        </div>
                    </th>
                    <th class="product-table-th product-table-th-number" data-column="stock" data-label="Stock" style="display: <?= (in_array('stock', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                        <div class="product-table-th-content">
                            <span class="product-table-th-icon"><i class="fas fa-warehouse"></i></span>
                            <span class="product-table-th-text">Stock</span>
                        </div>
                    </th>
                    <th class="product-table-th product-table-th-number" data-column="views" data-label="Views" style="display: <?= (in_array('views', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                        <div class="product-table-th-content">
                            <span class="product-table-th-icon"><i class="fas fa-eye"></i></span>
                            <span class="product-table-th-text">Views</span>
                        </div>
                    </th>
                    <th class="product-table-th" data-column="status" data-label="Status" style="display: <?= (in_array('status', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                        <div class="product-table-th-content">
                            <span class="product-table-th-icon"><i class="fas fa-toggle-on"></i></span>
                            <span class="product-table-th-text">Status</span>
                        </div>
                    </th>
                    <th class="product-table-th" data-column="featured" data-label="Featured" style="display: <?= (in_array('featured', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                        <div class="product-table-th-content">
                            <span class="product-table-th-icon"><i class="fas fa-star"></i></span>
                            <span class="product-table-th-text">Featured</span>
                        </div>
                    </th>
                    <th class="product-table-th" data-column="created" data-label="Created" style="display: <?= (in_array('created', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                        <div class="product-table-th-content">
                            <span class="product-table-th-icon"><i class="fas fa-calendar"></i></span>
                            <span class="product-table-th-text">Created</span>
                        </div>
                    </th>
                    <th class="product-table-th product-table-th-actions" data-column="actions" data-label="Actions" style="display: <?= (in_array('actions', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                        <div class="product-table-th-content">
                            <span class="product-table-th-icon"><i class="fas fa-cog"></i></span>
                            <span class="product-table-th-text">Actions</span>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody id="productsTableBody" class="bg-white divide-y divide-gray-100">
                <?php if (empty($products) && $page == 1): ?>
                    <tr>
                        <td colspan="15" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center max-w-md mx-auto">
                                <div class="relative mb-6">
                                    <div class="absolute inset-0 bg-gradient-to-r from-indigo-400 to-purple-400 rounded-full blur-xl opacity-20"></div>
                                    <div class="relative bg-gradient-to-br from-indigo-100 to-purple-100 rounded-full p-8">
                                        <i class="fas fa-box-open text-5xl text-indigo-600"></i>
                                </div>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-800 mb-2">No Products Found</h3>
                                <p class="text-gray-600 mb-6 text-center">Try adjusting your filters or add a new product to get started.</p>
                                <a href="<?= url('admin/product-edit.php') ?>" 
                                   class="group bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-8 py-3 rounded-xl font-semibold hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                                    <i class="fas fa-plus mr-2 group-hover:rotate-90 transition-transform"></i>
                                    Add Your First Product
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                    <tr class="product-row hover:bg-gradient-to-r hover:from-indigo-50/50 hover:to-purple-50/50 transition-all duration-200 border-b border-gray-100 group" data-product-id="<?= $product['id'] ?>">
                        <td class="px-4 py-4 whitespace-nowrap" data-column="checkbox" style="display: <?= (in_array('checkbox', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                            <input type="checkbox" class="product-checkbox w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 cursor-pointer" value="<?= $product['id'] ?>" onchange="updateBulkActions()">
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap" data-column="image" style="display: <?= (in_array('image', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                            <?php if (!empty($product['image'])): ?>
                                <div class="relative group-img">
                                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-400 to-purple-400 rounded-xl blur opacity-0 group-img-hover:opacity-20 transition-opacity"></div>
                                    <img src="<?= asset('storage/uploads/' . escape($product['image'])) ?>" 
                                         alt="" class="relative h-16 w-16 object-cover rounded-xl border-2 border-gray-200 group-hover:border-indigo-400 transition-all shadow-md group-hover:shadow-lg transform group-hover:scale-105">
                                </div>
                            <?php else: ?>
                                <div class="h-16 w-16 bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center group-hover:border-indigo-300 transition-colors">
                                    <i class="fas fa-image text-gray-400 group-hover:text-indigo-400 transition-colors"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4" data-column="name" style="display: <?= (in_array('name', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors"><?= escape($product['name']) ?></div>
                                <?php if (isset($variantCounts[$product['id']]) && $variantCounts[$product['id']] > 0): ?>
                                    <span class="px-2 py-0.5 text-xs bg-gradient-to-r from-purple-100 to-pink-100 text-purple-700 rounded-full font-medium border border-purple-200" title="<?= $variantCounts[$product['id']] ?> variant(s)">
                                        <i class="fas fa-layer-group mr-1"></i><?= $variantCounts[$product['id']] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($product['short_description'])): ?>
                                <div class="text-xs text-gray-500 line-clamp-1"><?= escape(substr($product['short_description'], 0, 60)) ?>...</div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap" data-column="sku" style="display: <?= (in_array('sku', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                            <span class="text-sm font-mono text-gray-600 bg-gray-50 px-2 py-1 rounded"><?= escape($product['sku'] ?? '-') ?></span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap" data-column="category" style="display: <?= (in_array('category', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                            <span class="text-sm text-gray-700 font-medium"><?= escape($product['category_name'] ?? 'Uncategorized') ?></span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap" data-column="price" style="display: <?= (in_array('price', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                            <?php if (!empty($product['sale_price']) && $product['sale_price'] > 0): ?>
                                <div class="text-indigo-600 font-bold text-base">$<?= number_format((float)$product['sale_price'], 2) ?></div>
                                <?php if (!empty($product['price']) && $product['price'] > 0): ?>
                                    <div class="text-xs text-gray-400 line-through">$<?= number_format((float)$product['price'], 2) ?></div>
                                <?php endif; ?>
                            <?php elseif (!empty($product['price']) && $product['price'] > 0): ?>
                                <div class="font-semibold text-gray-900">$<?= number_format((float)$product['price'], 2) ?></div>
                            <?php else: ?>
                                <span class="text-gray-400 text-sm">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap" data-column="sale_price" style="display: <?= (in_array('sale_price', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                            <?php if (!empty($product['sale_price']) && $product['sale_price'] > 0): ?>
                                <span class="text-sm font-semibold text-indigo-600">$<?= number_format((float)$product['sale_price'], 2) ?></span>
                            <?php else: ?>
                                <span class="text-gray-400 text-sm">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap" data-column="stock" style="display: <?= (in_array('stock', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                            <span class="px-3 py-1 text-xs font-medium rounded-full <?= 
                                $product['stock_status'] === 'in_stock' ? 'bg-green-100 text-green-700 border border-green-200' : 
                                ($product['stock_status'] === 'out_of_stock' ? 'bg-red-100 text-red-700 border border-red-200' : 
                                'bg-yellow-100 text-yellow-700 border border-yellow-200') 
                            ?>">
                                <?= ucwords(str_replace('_', ' ', $product['stock_status'])) ?>
                            </span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap" data-column="views" style="display: <?= (in_array('views', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                            <div class="flex items-center gap-1 text-sm text-gray-600">
                                <i class="fas fa-eye text-gray-400"></i>
                                <span class="font-medium"><?= number_format($product['view_count'] ?? 0) ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap" data-column="status" style="display: <?= (in_array('status', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                            <span class="px-3 py-1 text-xs font-medium rounded-full <?= $product['is_active'] ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200' ?>">
                                <?= $product['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap" data-column="featured" style="display: <?= (in_array('featured', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                            <?php if ($product['is_featured']): ?>
                                <span class="text-yellow-500 text-lg"><i class="fas fa-star"></i></span>
                            <?php else: ?>
                                <span class="text-gray-300 text-lg"><i class="far fa-star"></i></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap" data-column="created" style="display: <?= (in_array('created', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                            <div class="text-sm text-gray-600">
                                <div class="font-medium"><?= date('M d, Y', strtotime($product['created_at'])) ?></div>
                                <div class="text-xs text-gray-400"><?= date('g:i A', strtotime($product['created_at'])) ?></div>
                            </div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap" data-column="actions" style="display: <?= (in_array('actions', $selectedColumns) || empty($_GET['columns'])) ? '' : 'none' ?>;">
                            <div class="flex items-center gap-1.5">
                                <a href="<?= url('admin/product-edit.php?id=' . $product['id']) ?>" 
                                   class="group/btn bg-blue-50 hover:bg-blue-100 text-blue-600 p-2.5 rounded-lg transition-all duration-200 hover:scale-110 shadow-sm hover:shadow-md" 
                                   title="Edit">
                                    <i class="fas fa-edit text-sm"></i>
                                </a>
                                <a href="<?= url('admin/product-duplicate.php?id=' . $product['id']) ?>" 
                                   onclick="return confirm('Duplicate this product?')" 
                                   class="group/btn bg-purple-50 hover:bg-purple-100 text-purple-600 p-2.5 rounded-lg transition-all duration-200 hover:scale-110 shadow-sm hover:shadow-md" 
                                   title="Duplicate">
                                    <i class="fas fa-copy text-sm"></i>
                                </a>
                                <a href="?toggle_featured=<?= $product['id'] ?>" 
                                   class="group/btn bg-yellow-50 hover:bg-yellow-100 text-yellow-600 p-2.5 rounded-lg transition-all duration-200 hover:scale-110 shadow-sm hover:shadow-md" 
                                   title="Toggle Featured">
                                    <i class="fas fa-star text-sm"></i>
                                </a>
                                <a href="?delete=<?= $product['id'] ?>" 
                                   onclick="return confirm('Are you sure you want to delete this product?')" 
                                   class="group/btn bg-red-50 hover:bg-red-100 text-red-600 p-2.5 rounded-lg transition-all duration-200 hover:scale-110 shadow-sm hover:shadow-md" 
                                   title="Delete">
                                    <i class="fas fa-trash text-sm"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
            </div>
        </div>
        
        <!-- Modern Loading indicator for infinite scroll -->
        <div id="loadingIndicator" class="hidden text-center py-10">
            <div class="inline-flex flex-col items-center space-y-3">
                <div class="relative">
                    <div class="w-12 h-12 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                </div>
                <div class="flex items-center gap-2 text-indigo-600 font-medium">
                    <i class="fas fa-box-open"></i>
                <span>Loading more products...</span>
                </div>
            </div>
        </div>
        
        <!-- Modern End of list indicator -->
        <div id="endOfList" class="hidden text-center py-6">
            <div class="inline-flex items-center gap-2 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 px-6 py-3 rounded-full">
                <i class="fas fa-check-circle text-green-600"></i>
                <span class="text-green-700 font-medium">All products loaded</span>
            </div>
        </div>
        
        <!-- Modern Pagination Controls -->
        <?php
        // Calculate total pages (totalCount is already calculated above)
            $totalPages = max(1, (int)ceil($totalCount / $limit));
        
        // Always show pagination if totalCount > 0 and (totalCount > limit OR page > 1)
        // This ensures pagination is visible when there are multiple pages
        if ($totalCount > 0 && ($totalCount > $limit || $page > 1)):
        ?>
        <div class="bg-gradient-to-r from-gray-50 to-white border-t-2 border-gray-200 px-6 py-4 flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center text-sm text-gray-700 flex-wrap gap-3">
                <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-lg border border-gray-200 shadow-sm">
                    <i class="fas fa-file-alt text-indigo-500"></i>
                    <span class="font-medium">Page</span>
                    <span class="font-bold text-indigo-600"><?= $page ?></span>
                    <span class="text-gray-400">of</span>
                    <span class="font-bold text-gray-900"><?= $totalPages ?></span>
                </div>
                <div class="text-sm text-gray-600 bg-gray-50 px-3 py-2 rounded-lg">
                    <i class="fas fa-cube mr-1 text-gray-400"></i>
                    <span class="font-medium"><?= number_format($totalCount) ?></span> total
                </div>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" 
                       class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all duration-200" title="First Page">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                       class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all duration-200" title="Previous Page">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php else: ?>
                    <span class="px-3 py-2 text-sm border border-gray-200 rounded-lg text-gray-300 cursor-not-allowed bg-gray-50">
                        <i class="fas fa-angle-double-left"></i>
                    </span>
                    <span class="px-3 py-2 text-sm border border-gray-200 rounded-lg text-gray-300 cursor-not-allowed bg-gray-50">
                        <i class="fas fa-angle-left"></i>
                    </span>
                <?php endif; ?>
                
                <!-- Page Numbers -->
                <div class="flex items-center gap-1">
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    if ($startPage > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" 
                           class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">1</a>
                        <?php if ($startPage > 2): ?>
                            <span class="px-2 text-gray-400">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="px-4 py-2 text-sm bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg font-bold shadow-md"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                               class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all duration-200 font-medium"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="px-2 text-gray-400">...</span>
                        <?php endif; ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" 
                           class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"><?= $totalPages ?></a>
                    <?php endif; ?>
                </div>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                       class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all duration-200" title="Next Page">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" 
                       class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all duration-200" title="Last Page">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php else: ?>
                    <span class="px-3 py-2 text-sm border border-gray-200 rounded-lg text-gray-300 cursor-not-allowed bg-gray-50">
                        <i class="fas fa-angle-right"></i>
                    </span>
                    <span class="px-3 py-2 text-sm border border-gray-200 rounded-lg text-gray-300 cursor-not-allowed bg-gray-50">
                        <i class="fas fa-angle-double-right"></i>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Infinite scroll variables
let currentPage = <?= $page ?>;
let isLoading = false;
// Check if there are more products to load
let hasMore = <?= ($totalCount > ($page * $limit)) ? 'true' : 'false' ?>;
const productsPerPage = <?= $limit ?>;
const totalProductsCount = <?= $totalCount ?>;

// Get current filter parameters
function getFilterParams() {
    const urlParams = new URLSearchParams(window.location.search);
    return {
        search: urlParams.get('search') || '',
        category: urlParams.get('category') || '',
        status: urlParams.get('status') || '',
        featured: urlParams.get('featured') || '',
        sort: urlParams.get('sort') || 'name_asc',
        date_from: urlParams.get('date_from') || '',
        date_to: urlParams.get('date_to') || '',
        price_min: urlParams.get('price_min') || '',
        price_max: urlParams.get('price_max') || ''
    };
}

// Load more products
function loadMoreProducts() {
    if (isLoading || !hasMore) {
        return;
    }
    
    isLoading = true;
    currentPage++;
    
    const loadingIndicator = document.getElementById('loadingIndicator');
    const endOfList = document.getElementById('endOfList');
    const tbody = document.getElementById('productsTableBody');
    
    // Show loading indicator
    if (loadingIndicator) {
    loadingIndicator.classList.remove('hidden');
    }
    
    const params = getFilterParams();
    params.page = currentPage;
    params.limit = productsPerPage;
    
    const queryString = new URLSearchParams(params).toString();
    
    fetch('<?= url('admin/api/products-load.php') ?>?' + queryString)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.products && data.products.length > 0) {
                appendProducts(data.products);
                hasMore = data.pagination.has_more;
                
                // Update showing count
                const showingCount = document.getElementById('showingCount');
                if (showingCount) {
                    const currentCount = parseInt(showingCount.textContent.replace(/,/g, '')) || 0;
                    const newCount = currentCount + data.products.length;
                    showingCount.textContent = newCount.toLocaleString();
                }
                
                // Update total count display if it exists
                const totalCountElement = showingCount?.nextElementSibling;
                if (totalCountElement && totalCountElement.textContent.includes('of')) {
                    totalCountElement.textContent = `of ${data.pagination.total.toLocaleString()}`;
                }
            } else {
                hasMore = false;
            }
            
            if (!hasMore && endOfList) {
                endOfList.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error loading products:', error);
            hasMore = false;
            // Show error message
            if (endOfList) {
                endOfList.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Error loading products. Please refresh the page.';
                endOfList.classList.remove('hidden');
            }
        })
        .finally(() => {
            isLoading = false;
            if (loadingIndicator) {
            loadingIndicator.classList.add('hidden');
            }
        });
}

// Append products to table
function appendProducts(products) {
    const tbody = document.getElementById('productsTableBody');
    const selectedColumns = <?= json_encode($selectedColumns) ?>;
    
    products.forEach(product => {
        const row = createProductRow(product, selectedColumns);
        tbody.appendChild(row);
    });
}

// Create product row HTML
function createProductRow(product, selectedColumns) {
    const tr = document.createElement('tr');
    tr.className = 'product-row hover:bg-blue-50/50 transition-colors border-b border-gray-100';
    tr.setAttribute('data-product-id', product.id);
    
    const variantBadge = product.variant_count > 0 
        ? `<span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded-full" title="${product.variant_count} variant(s)">
            <i class="fas fa-layer-group mr-1"></i>${product.variant_count}
           </span>`
        : '';
    
    const imageHtml = product.image 
        ? `<div class="relative group">
             <img src="<?= asset('storage/uploads/') ?>${escapeHtml(product.image)}" 
                  alt="" class="h-14 w-14 object-cover rounded-lg border-2 border-gray-200 group-hover:border-blue-400 transition-all shadow-sm">
             <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 rounded-lg transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                 <i class="fas fa-eye text-white text-xs"></i>
             </div>
           </div>`
        : `<div class="h-14 w-14 bg-gray-100 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center">
             <i class="fas fa-image text-gray-400 text-sm"></i>
           </div>`;
    
    const priceHtml = product.sale_price > 0
        ? `<div class="text-blue-600 font-bold">$${formatNumber(product.sale_price)}</div>
           ${product.price > 0 ? `<div class="text-xs text-gray-400 line-through">$${formatNumber(product.price)}</div>` : ''}`
        : (product.price > 0 
            ? `<div class="font-semibold">$${formatNumber(product.price)}</div>`
            : `<span class="text-gray-400">-</span>`);
    
    const stockStatusClass = product.stock_status === 'in_stock' 
        ? 'bg-green-100 text-green-800'
        : (product.stock_status === 'out_of_stock' 
            ? 'bg-red-100 text-red-800'
            : 'bg-yellow-100 text-yellow-800');
    
    const stockStatusText = product.stock_status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    
    const createdDate = new Date(product.created_at).toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric' 
    });
    
    tr.innerHTML = `
        <td class="px-6 py-4 whitespace-nowrap" data-column="checkbox" style="display: ${shouldShowColumn('checkbox', selectedColumns) ? '' : 'none'};">
            <input type="checkbox" class="product-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" value="${product.id}" onchange="updateBulkActions()">
        </td>
        <td class="px-6 py-4 whitespace-nowrap" data-column="image" style="display: ${shouldShowColumn('image', selectedColumns) ? '' : 'none'};">
            ${imageHtml}
        </td>
        <td class="px-6 py-4" data-column="name" style="display: ${shouldShowColumn('name', selectedColumns) ? '' : 'none'};">
            <div class="flex items-center gap-2">
                <div class="text-sm font-medium text-gray-900">${escapeHtml(product.name)}</div>
                ${variantBadge}
            </div>
            ${product.short_description ? `<div class="text-xs text-gray-500 line-clamp-1">${escapeHtml(product.short_description.substring(0, 50))}...</div>` : ''}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-column="sku" style="display: ${shouldShowColumn('sku', selectedColumns) ? '' : 'none'};">
            ${escapeHtml(product.sku || '-')}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-column="category" style="display: ${shouldShowColumn('category', selectedColumns) ? '' : 'none'};">
            ${escapeHtml(product.category_name || 'Uncategorized')}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" data-column="price" style="display: ${shouldShowColumn('price', selectedColumns) ? '' : 'none'};">
            ${priceHtml}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm" data-column="sale_price" style="display: ${shouldShowColumn('sale_price', selectedColumns) ? '' : 'none'};">
            ${product.sale_price > 0 ? '$' + formatNumber(product.sale_price) : '-'}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm" data-column="stock" style="display: ${shouldShowColumn('stock', selectedColumns) ? '' : 'none'};">
            <span class="px-2 py-1 text-xs rounded ${stockStatusClass}">${stockStatusText}</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-column="views" style="display: ${shouldShowColumn('views', selectedColumns) ? '' : 'none'};">
            ${formatNumber(product.view_count || 0)}
        </td>
        <td class="px-6 py-4 whitespace-nowrap" data-column="status" style="display: ${shouldShowColumn('status', selectedColumns) ? '' : 'none'};">
            <span class="px-2 py-1 text-xs rounded ${product.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                ${product.is_active ? 'Active' : 'Inactive'}
            </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap" data-column="featured" style="display: ${shouldShowColumn('featured', selectedColumns) ? '' : 'none'};">
            ${product.is_featured 
                ? '<span class="text-yellow-500"><i class="fas fa-star"></i></span>'
                : '<span class="text-gray-300"><i class="far fa-star"></i></span>'}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-column="created" style="display: ${shouldShowColumn('created', selectedColumns) ? '' : 'none'};">
            ${createdDate}
        </td>
        <td class="px-6 py-4 whitespace-nowrap" data-column="actions" style="display: ${shouldShowColumn('actions', selectedColumns) ? '' : 'none'};">
            <div class="flex items-center space-x-2">
                <a href="<?= url('admin/product-edit.php?id=') ?>${product.id}" 
                   class="bg-blue-100 hover:bg-blue-200 text-blue-700 p-2 rounded-lg transition-all" title="Edit">
                    <i class="fas fa-edit text-sm"></i>
                </a>
                <a href="<?= url('admin/product-duplicate.php?id=') ?>${product.id}" 
                   onclick="return confirm('Duplicate this product?')" 
                   class="bg-purple-100 hover:bg-purple-200 text-purple-700 p-2 rounded-lg transition-all" title="Duplicate">
                    <i class="fas fa-copy text-sm"></i>
                </a>
                <a href="?toggle_featured=${product.id}" 
                   class="bg-yellow-100 hover:bg-yellow-200 text-yellow-700 p-2 rounded-lg transition-all" title="Toggle Featured">
                    <i class="fas fa-star text-sm"></i>
                </a>
                <a href="?delete=${product.id}" 
                   onclick="return confirm('Are you sure you want to delete this product?')" 
                   class="bg-red-100 hover:bg-red-200 text-red-700 p-2 rounded-lg transition-all" title="Delete">
                    <i class="fas fa-trash text-sm"></i>
                </a>
            </div>
        </td>
    `;
    
    return tr;
}

// Helper functions
function shouldShowColumn(column, selectedColumns) {
    return selectedColumns.includes(column) || selectedColumns.length === 0;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatNumber(num) {
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Infinite scroll detection - improved with throttling
let scrollTimeout;
function handleScroll() {
    clearTimeout(scrollTimeout);
    scrollTimeout = setTimeout(() => {
        // Check if user scrolled near bottom (within 500px)
        const scrollPosition = window.innerHeight + window.scrollY;
        const pageHeight = document.documentElement.offsetHeight;
        const threshold = 500; // Load when 500px from bottom
        
        if (scrollPosition >= pageHeight - threshold && !isLoading && hasMore) {
        loadMoreProducts();
    }
    }, 100); // Throttle to check every 100ms
}

window.addEventListener('scroll', handleScroll, { passive: true });

// Reset on filter change
document.addEventListener('DOMContentLoaded', () => {
    // Reset pagination when filters change
    const filterForm = document.getElementById('filter-form-products-filter');
    if (filterForm) {
        filterForm.addEventListener('submit', () => {
            currentPage = 1;
            hasMore = true;
            document.getElementById('endOfList').classList.add('hidden');
        });
    }
});

function toggleAll(checkbox) {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateBulkActions();
}

function updateBulkActions() {
    const checked = document.querySelectorAll('.product-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    
    if (checked.length > 0) {
        bulkActions.classList.remove('hidden');
    } else {
        bulkActions.classList.add('hidden');
    }
}

function clearSelection() {
    document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateBulkActions();
}

function executeBulkAction() {
    const action = document.getElementById('bulkActionSelect').value;
    const checked = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value);
    
    if (!action || checked.length === 0) {
        alert('Please select an action and at least one product.');
        return;
    }
    
    if (action === 'delete' && !confirm(`Delete ${checked.length} product(s)?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', action);
    checked.forEach(id => formData.append('product_ids[]', id));
    
    fetch('<?= url('admin/products-bulk.php') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>

<style>
/* Cache-busting: Force reload styles */
/* Version: <?= $cacheVersion ?> */

/* Modern Animations */
@keyframes slide-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-slide-in {
    animation: slide-in 0.3s ease-out;
}

/* Enhanced hover effects */
.product-row {
    transition: all 0.2s ease;
}

.product-row:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
}

/* Glassmorphism effect for header */
.backdrop-blur-sm {
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

/* Smooth transitions */
* {
    transition-property: color, background-color, border-color, transform, box-shadow;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Custom scrollbar for table */
.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(to right, #6366f1, #8b5cf6);
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to right, #4f46e5, #7c3aed);
}

/* Interactive Table Header */
.product-table-header {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #e2e8f0 100%);
    border-bottom: 2px solid #cbd5e1;
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.product-table-th {
    padding: 1rem 1rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #1e293b;
    white-space: nowrap;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    border-right: 1px solid rgba(203, 213, 225, 0.5);
}

.product-table-th:last-child {
    border-right: none;
}

.product-table-th:hover {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    color: #3b82f6;
    transform: translateY(-1px);
    box-shadow: inset 0 -2px 0 #3b82f6;
}

.product-table-th-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: fit-content;
}

.product-table-th-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    flex-shrink: 0;
    color: #64748b;
    transition: all 0.3s ease;
    font-size: 0.875rem;
}

.product-table-th:hover .product-table-th-icon {
    color: #3b82f6;
    transform: scale(1.15) rotate(5deg);
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border-radius: 0.375rem;
    padding: 0.25rem;
}

.product-table-th-text {
    font-weight: 700;
    color: inherit;
    transition: color 0.2s ease;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
}

/* Responsive: Hide icons on very tight spaces */
@media (max-width: 1024px) {
    .product-table-th-icon {
        display: none;
    }
    
    .product-table-th-content {
        gap: 0;
    }
}

/* For extremely tight spaces, show only icons */
@media (max-width: 768px) {
    .product-table-th-text {
        display: none;
    }
    
    .product-table-th-icon {
        display: flex;
        width: 1.75rem;
        height: 1.75rem;
    }
    
    .product-table-th:hover .product-table-th-icon {
        transform: scale(1.2);
    }
}

/* Special column styles */
.product-table-th-checkbox {
    width: 3rem;
    min-width: 3rem;
    max-width: 3rem;
    padding: 1rem 0.75rem;
    text-align: center;
}

.product-table-th-checkbox:hover {
    background: transparent;
    transform: none;
    box-shadow: none;
}

.product-table-checkbox {
    width: 1.125rem;
    height: 1.125rem;
    cursor: pointer;
    accent-color: #6366f1;
    transition: all 0.2s ease;
}

.product-table-checkbox:hover {
    transform: scale(1.1);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.product-table-th-number {
    text-align: right;
}

.product-table-th-number .product-table-th-content {
    justify-content: flex-end;
}

.product-table-th-actions {
    text-align: center;
    width: 6rem;
    min-width: 6rem;
}

.product-table-th-actions .product-table-th-content {
    justify-content: center;
}

/* Add subtle animation on load */
@keyframes headerSlideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.product-table-header {
    animation: headerSlideIn 0.4s ease-out;
}

/* Tooltip on hover for mobile (when text is hidden) */
@media (max-width: 768px) {
    .product-table-th {
        position: relative;
    }
    
    .product-table-th:hover::after {
        content: attr(data-label);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: #1e293b;
        color: white;
        padding: 0.5rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        white-space: nowrap;
        z-index: 20;
        margin-bottom: 0.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .product-table-th:hover::before {
        content: '';
        position: absolute;
        bottom: calc(100% - 0.25rem);
        left: 50%;
        transform: translateX(-50%);
        border: 4px solid transparent;
        border-top-color: #1e293b;
        z-index: 20;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
