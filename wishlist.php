<?php
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Product;

$productModel = new Product();

// Get wishlist from session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$wishlistIds = $_SESSION['wishlist'] ?? [];
$products = [];

foreach ($wishlistIds as $id) {
    $product = $productModel->getById($id);
    if ($product && $product['is_active']) {
        $products[] = $product;
    }
}

$pageTitle = 'My Wishlist - ' . get_site_name();
include __DIR__ . '/includes/header.php';
?>

<main class="py-8">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold mb-6">My Wishlist</h1>
        
        <?php if (empty($products)): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-12 text-center">
                <i class="fas fa-heart text-6xl text-blue-400 mb-4"></i>
                <h2 class="text-2xl font-bold mb-2">Your Wishlist is Empty</h2>
                <p class="text-gray-600 mb-6">Start adding products to your wishlist by clicking the heart icon on product pages.</p>
                <a href="<?= url('products.php') ?>" class="btn-primary inline-block">
                    Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($products as $product): ?>
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
                            <button onclick="event.preventDefault(); removeFromWishlist(<?= $product['id'] ?>)" 
                                    class="absolute top-2 right-2 bg-white rounded-full p-2 shadow-lg hover:bg-red-500 hover:text-white transition-colors">
                                <i class="fas fa-heart text-red-500"></i>
                            </button>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-lg mb-2 line-clamp-2"><?= escape($product['name']) ?></h3>
                            <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?= escape($product['short_description'] ?? '') ?></p>
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-blue-600">$<?= number_format($product['price'], 2) ?></span>
                                <span class="btn-primary-sm">View</span>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
function removeFromWishlist(productId) {
    fetch('<?= url('api/wishlist.php') ?>?action=remove&id=' + productId, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error removing from wishlist');
        }
    })
    .catch(error => {
        console.error('Error removing from wishlist:', error);
        alert('Error removing from wishlist. Please try again.');
    });
}

// Update wishlist count
document.addEventListener('DOMContentLoaded', function() {
    updateWishlistCount();
});

function updateWishlistCount() {
    fetch('<?= url('api/wishlist.php') ?>?action=count', {
        credentials: 'include'
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            const countEl = document.getElementById('wishlist-count');
            if (countEl && data.count > 0) {
                countEl.textContent = data.count;
                countEl.classList.remove('hidden');
            } else if (countEl) {
                countEl.classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Error updating wishlist count:', error);
        });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

