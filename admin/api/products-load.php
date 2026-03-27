<?php
/**
 * API Endpoint for Loading Products with Pagination
 * Used for infinite scroll
 */

require_once __DIR__ . '/../../bootstrap/app.php';
require_once __DIR__ . '/../includes/auth.php';

use App\Models\Product;
use App\Models\Category;

header('Content-Type: application/json');

$productModel = new Product();
$categoryModel = new Category();

// Get parameters
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 20);
$search = trim($_GET['search'] ?? '');
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$featuredFilter = $_GET['featured'] ?? '';
$sort = $_GET['sort'] ?? 'name_asc';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$priceMin = !empty($_GET['price_min']) ? (float)$_GET['price_min'] : null;
$priceMax = !empty($_GET['price_max']) ? (float)$_GET['price_max'] : null;

// Build filter parameters
// IMPORTANT: include_inactive = true means show ALL products (active and inactive)
// Only filter by active status if user explicitly selects a status filter
$filterParams = [
    'include_inactive' => true,
    'page' => $page,
    'limit' => $limit
];

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
    unset($filterParams['include_inactive']);
} elseif ($statusFilter === 'inactive') {
    $filterParams['is_active'] = 0;
    unset($filterParams['include_inactive']);
}
// If no status filter, keep include_inactive = true to show ALL products

if ($featuredFilter === 'yes') {
    $filterParams['is_featured'] = 1;
} elseif ($featuredFilter === 'no') {
    $filterParams['is_featured'] = 0;
}

// Map sort parameter
$sortMap = [
    'name_asc' => 'name',
    'name_desc' => 'name_desc',
    'price_asc' => 'price_asc',
    'price_desc' => 'price_desc',
    'date_desc' => 'newest',
    'date_asc' => 'newest'
];
$filterParams['sort'] = $sortMap[$sort] ?? 'name';

// Add price filters to filterParams for SQL query (more efficient)
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

// Get variant counts
$variantCounts = [];
try {
    if (!empty($products)) {
        $productIds = array_column($products, 'id');
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $variantData = db()->fetchAll(
            "SELECT product_id, COUNT(*) as count FROM product_variants WHERE is_active = 1 AND product_id IN ($placeholders) GROUP BY product_id",
            $productIds
        );
        foreach ($variantData as $v) {
            $variantCounts[$v['product_id']] = $v['count'];
        }
    }
} catch (Exception $e) {
    // Variants table might not exist
}

// Get total count for pagination
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
    $totalCount = db()->fetchOne("SELECT COUNT(*) as count FROM products p $whereClause", $params)['count'];
} catch (Exception $e) {
    $totalCount = count($products);
}

$hasMore = ($page * $limit) < $totalCount;

// Format products for response
$formattedProducts = [];
foreach ($products as $product) {
    $formattedProducts[] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'image' => $product['image'] ?? '',
        'sku' => $product['sku'] ?? '-',
        'category_name' => $product['category_name'] ?? 'Uncategorized',
        'price' => $product['price'] ?? 0,
        'sale_price' => $product['sale_price'] ?? null,
        'stock_status' => $product['stock_status'] ?? 'in_stock',
        'view_count' => $product['view_count'] ?? 0,
        'is_active' => $product['is_active'] ?? 0,
        'is_featured' => $product['is_featured'] ?? 0,
        'created_at' => $product['created_at'],
        'short_description' => $product['short_description'] ?? '',
        'variant_count' => $variantCounts[$product['id']] ?? 0
    ];
}

echo json_encode([
    'success' => true,
    'products' => $formattedProducts,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $totalCount,
        'has_more' => $hasMore
    ]
]);

