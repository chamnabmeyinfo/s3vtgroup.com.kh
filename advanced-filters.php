<?php
/**
 * Advanced Filter Sidebar with Visual Controls
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
$rating = isset($_GET['rating']) ? (int)$_GET['rating'] : null;

// Get all categories
$allCategories = $categoryModel->getAll();

// Get all products for price range calculation
$allProducts = $productModel->getAll();
$prices = array_map(fn($p) => $p['sale_price'] ?? $p['price'], $allProducts);
$minPriceRange = $prices ? min($prices) : 0;
$maxPriceRange = $prices ? max($prices) : 10000;

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

// Get products
$products = $productModel->getAll($filters);

// Apply additional filters
$filteredProducts = array_filter($products, function($product) use ($minPrice, $maxPrice, $inStock, $rating) {
    $price = $product['sale_price'] ?? $product['price'];
    
    if ($minPrice !== null && $price < $minPrice) return false;
    if ($maxPrice !== null && $price > $maxPrice) return false;
    if ($inStock !== null && $product['stock_status'] !== 'in_stock') return false;
    
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
            <!-- Advanced Filters Sidebar -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-20 space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-bold">Filters</h2>
                        <button onclick="clearAllFilters()" class="text-sm text-blue-600 hover:underline">Clear All</button>
                    </div>
                    
                    <form method="GET" action="" id="filter-form">
                        <!-- Search -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Search</label>
                            <input type="text" name="search" value="<?= escape($search) ?>"
                                   placeholder="Search products..."
                                   class="w-full px-4 py-2 border rounded-lg"
                                   onkeyup="debounceFilter()">
                        </div>
                        
                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Category</label>
                            <select name="category" class="w-full px-4 py-2 border rounded-lg" onchange="applyFilters()">
                                <option value="">All Categories</option>
                                <?php foreach ($allCategories as $cat): ?>
                                    <option value="<?= escape($cat['slug']) ?>" <?= $category === $cat['slug'] ? 'selected' : '' ?>>
                                        <?= escape($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Price Range Slider -->
                        <div>
                            <label class="block text-sm font-medium mb-2">
                                Price Range: $<span id="price-display-min"><?= number_format($minPrice ?: $minPriceRange, 0) ?></span> - $<span id="price-display-max"><?= number_format($maxPrice ?: $maxPriceRange, 0) ?></span>
                            </label>
                            <div class="relative">
                                <input type="range" 
                                       id="price-min" 
                                       min="<?= $minPriceRange ?>" 
                                       max="<?= $maxPriceRange ?>" 
                                       value="<?= $minPrice ?: $minPriceRange ?>"
                                       step="100"
                                       class="w-full"
                                       oninput="updatePriceRange()">
                                <input type="range" 
                                       id="price-max" 
                                       min="<?= $minPriceRange ?>" 
                                       max="<?= $maxPriceRange ?>" 
                                       value="<?= $maxPrice ?: $maxPriceRange ?>"
                                       step="100"
                                       class="w-full mt-2"
                                       oninput="updatePriceRange()">
                            </div>
                            <input type="hidden" name="min_price" id="min_price" value="<?= $minPrice ?>">
                            <input type="hidden" name="max_price" id="max_price" value="<?= $maxPrice ?>">
                        </div>
                        
                        <!-- Stock Status -->
                        <div>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="in_stock" value="1" <?= $inStock ? 'checked' : '' ?>
                                       class="mr-2 w-5 h-5"
                                       onchange="applyFilters()">
                                <span class="text-sm">In Stock Only</span>
                            </label>
                        </div>
                        
                        <!-- Sort -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Sort By</label>
                            <select name="sort" class="w-full px-4 py-2 border rounded-lg" onchange="applyFilters()">
                                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A-Z</option>
                                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="md:col-span-3">
                <?php if (empty($filteredProducts)): ?>
                    <div class="bg-white rounded-lg shadow-md p-12 text-center">
                        <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                        <h2 class="text-2xl font-bold mb-2">No Products Found</h2>
                        <p class="text-gray-600 mb-6">Try adjusting your filters</p>
                        <button onclick="clearAllFilters()" class="btn-primary">Clear Filters</button>
                    </div>
                <?php else: ?>
                    <div class="products-infinite-container grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($filteredProducts as $product): ?>
                        <div class="product-card bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                            <a href="<?= url('product.php?slug=' . escape($product['slug'])) ?>">
                                <div class="w-full aspect-[10/7] bg-gray-200 flex items-center justify-center overflow-hidden relative group">
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="<?= asset('storage/uploads/' . escape($product['image'])) ?>" 
                                             alt="<?= escape($product['name']) ?>" 
                                             class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="text-gray-400">No Image</span>
                                    <?php endif; ?>
                                    <!-- Quick Add Button -->
                                    <button onclick="event.preventDefault(); quickAddToCart(<?= $product['id'] ?>);"
                                            class="absolute bottom-2 right-2 bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-blue-700 shadow-lg"
                                            data-quick-add-cart="<?= $product['id'] ?>"
                                            title="Quick Add to Cart">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
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

<script src="<?= asset('assets/js/advanced-ux.js') ?>"></script>
<script>
function updatePriceRange() {
    const minSlider = document.getElementById('price-min');
    const maxSlider = document.getElementById('price-max');
    const minPrice = parseInt(minSlider.value);
    const maxPrice = parseInt(maxSlider.value);
    
    // Ensure min doesn't exceed max
    if (minPrice > maxPrice) {
        minSlider.value = maxPrice;
    }
    if (maxPrice < minPrice) {
        maxSlider.value = minPrice;
    }
    
    document.getElementById('price-display-min').textContent = parseInt(minSlider.value).toLocaleString();
    document.getElementById('price-display-max').textContent = parseInt(maxSlider.value).toLocaleString();
    document.getElementById('min_price').value = minSlider.value;
    document.getElementById('max_price').value = maxSlider.value;
    
    debounceFilter();
}

function applyFilters() {
    document.getElementById('filter-form').submit();
}

function clearAllFilters() {
    window.location.href = window.location.pathname;
}

let filterTimeout;
function debounceFilter() {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
}

function quickAddToCart(productId) {
    fetch('<?= url('api/cart.php') ?>?action=add&product_id=' + productId, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Product added to cart!', 'success');
            updateCartCount();
        }
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

