<?php
/**
 * API: Load More Products (AJAX)
 */
require_once __DIR__ . '/../bootstrap/app.php';

// CORS headers
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'https://www.s3vtgroup.com.kh',
    'https://s3vtgroup.com.kh',
    'http://localhost',
    'http://127.0.0.1'
];

if (in_array($origin, $allowedOrigins) || strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

// Start session for admin check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Models\Product;
use App\Models\Category;

$productModel = new Product();
$categoryModel = new Category();

$filters = [
    'page' => (int)($_GET['page'] ?? 1),
    'limit' => 12
];

if (!empty($_GET['category'])) {
    $category = $categoryModel->getBySlug($_GET['category']);
    if ($category) {
        $filters['category_id'] = $category['id'];
    }
}

if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

if (!empty($_GET['featured'])) {
    $filters['featured'] = true;
}

$products = $productModel->getAll($filters);
$totalProducts = $productModel->count($filters);
$totalPages = ceil($totalProducts / $filters['limit']);
$hasMore = $filters['page'] < $totalPages;

// Check if admin is logged in
$isAdmin = session('admin_logged_in') ?? false;

// Generate HTML for products using the same structure as products.php
ob_start();
foreach ($products as $product):
?>
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
                <?php if ($isAdmin): ?>
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
<?php
endforeach;
$html = ob_get_clean();

echo json_encode([
    'success' => true,
    'html' => $html,
    'hasMore' => $hasMore,
    'currentPage' => $filters['page'],
    'totalPages' => $totalPages,
    'totalProducts' => $totalProducts
]);

