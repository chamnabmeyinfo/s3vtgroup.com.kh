<?php
/**
 * Advanced Product Filters Page
 */
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Product;
use App\Models\Category;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$productModel = new Product();
$categoryModel = new Category();

// Get filter parameters
$category = $_GET['category'] ?? '';
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'name';
$inStock = isset($_GET['in_stock']) ? (int)$_GET['in_stock'] : null;

// Build filter conditions
$filters = [];
if ($category) {
    $cat = $categoryModel->getBySlug($category);
    if ($cat) {
        $filters['category_id'] = $cat['id'];
    }
}
if ($search) {
    $filters['search'] = $search;
}

// Get all categories for filter dropdown
$allCategories = $categoryModel->getAll();

// Get products
$allProducts = $productModel->getAll($filters);

// Apply additional filters
$filteredProducts = array_filter($allProducts, function($product) use ($minPrice, $maxPrice, $inStock) {
    $price = $product['sale_price'] ?? $product['price'];
    
    if ($minPrice !== null && $price < $minPrice) {
        return false;
    }
    
    if ($maxPrice !== null && $price > $maxPrice) {
        return false;
    }
    
    if ($inStock !== null && $product['stock_status'] !== 'in_stock') {
        return false;
    }
    
    return true;
});

// Sort products
if ($sort === 'price_asc') {
    usort($filteredProducts, fn($a, $b) => ($a['sale_price'] ?? $a['price']) <=> ($b['sale_price'] ?? $b['price']));
} elseif ($sort === 'price_desc') {
    usort($filteredProducts, fn($a, $b) => ($b['sale_price'] ?? $b['price']) <=> ($a['sale_price'] ?? $a['price']));
} elseif ($sort === 'name') {
    usort($filteredProducts, fn($a, $b) => strcmp($a['name'], $b['name']));
}

// Calculate price range
$prices = array_map(fn($p) => $p['sale_price'] ?? $p['price'], $allProducts);
$minPriceRange = $prices ? min($prices) : 0;
$maxPriceRange = $prices ? max($prices) : 10000;

$pageTitle = 'Browse Products - ' . get_site_name();
include __DIR__ . '/includes/header.php';
?>

<main class="py-8">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Browse Products</h1>
            <div class="text-gray-600">
                Showing <?= count($filteredProducts) ?> product(s)
            </div>
        </div>
        
        <div class="grid md:grid-cols-4 gap-8">
            <!-- Filters Sidebar -->
            <div class="bg-white rounded-lg shadow-md p-6 h-fit sticky top-20">
                <h2 class="text-xl font-bold mb-4">Filters</h2>
                
                <form method="GET" action="" class="space-y-6">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Search</label>
                        <input type="text" name="search" value="<?= escape($search) ?>"
                               placeholder="Search products..."
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    
                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Category</label>
                        <select name="category" class="w-full px-4 py-2 border rounded-lg">
                            <option value="">All Categories</option>
                            <?php foreach ($allCategories as $cat): ?>
                                <option value="<?= escape($cat['slug']) ?>" <?= $category === $cat['slug'] ? 'selected' : '' ?>>
                                    <?= escape($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Price Range -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Price Range</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="min_price" placeholder="Min"
                                   value="<?= $minPrice ?>" min="0" step="0.01"
                                   class="px-3 py-2 border rounded-lg">
                            <input type="number" name="max_price" placeholder="Max"
                                   value="<?= $maxPrice ?>" min="0" step="0.01"
                                   class="px-3 py-2 border rounded-lg">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Range: $<?= number_format($minPriceRange, 2) ?> - $<?= number_format($maxPriceRange, 2) ?>
                        </p>
                    </div>
                    
                    <!-- Stock Status -->
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="in_stock" value="1" <?= $inStock ? 'checked' : '' ?>
                                   class="mr-2">
                            <span class="text-sm">In Stock Only</span>
                        </label>
                    </div>
                    
                    <!-- Sort -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Sort By</label>
                        <select name="sort" class="w-full px-4 py-2 border rounded-lg">
                            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name</option>
                            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-primary w-full">Apply Filters</button>
                    <a href="<?= url('products.php') ?>" class="btn-secondary w-full text-center block">Reset</a>
                </form>
            </div>
            
            <!-- Products Grid -->
            <div class="md:col-span-3">
                <?php if (empty($filteredProducts)): ?>
                    <div class="bg-white rounded-lg shadow-md p-12 text-center">
                        <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                        <h2 class="text-2xl font-bold mb-2">No Products Found</h2>
                        <p class="text-gray-600 mb-6">Try adjusting your filters</p>
                        <a href="<?= url('products.php') ?>" class="btn-primary inline-block">View All Products</a>
                    </div>
                <?php else: ?>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($filteredProducts as $product): ?>
                        <div class="product-card bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                            <a href="<?= url('product.php?slug=' . escape($product['slug'])) ?>">
                                <div class="w-full aspect-[10/7] bg-gray-200 flex items-center justify-center overflow-hidden relative">
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="<?= asset('storage/uploads/' . escape($product['image'])) ?>" 
                                             alt="<?= escape($product['name']) ?>" 
                                             class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="text-gray-400">No Image</span>
                                    <?php endif; ?>
                                </div>
                                <div class="p-4">
                                    <h3 class="font-bold text-lg mb-2 line-clamp-2"><?= escape($product['name']) ?></h3>
                                    <?php if ($product['sale_price']): ?>
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg font-bold text-blue-600">$<?= number_format($product['sale_price'], 2) ?></span>
                                            <span class="text-sm text-gray-400 line-through">$<?= number_format($product['price'], 2) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-lg font-bold text-blue-600">$<?= number_format($product['price'], 2) ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

