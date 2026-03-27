<?php
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Product;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$productModel = new Product();
$recentIds = $_SESSION['recently_viewed'] ?? [];
$products = [];

foreach ($recentIds as $id) {
    $product = $productModel->getById($id);
    if ($product && $product['is_active']) {
        $products[] = $product;
    }
}

$pageTitle = 'Recently Viewed Products - ' . get_site_name();
include __DIR__ . '/includes/header.php';
?>

<main class="py-8">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold mb-6">Recently Viewed Products</h1>
        
        <?php if (empty($products)): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-12 text-center">
                <i class="fas fa-clock text-6xl text-blue-400 mb-4"></i>
                <h2 class="text-2xl font-bold mb-2">No Recently Viewed Products</h2>
                <p class="text-gray-600 mb-6">Start browsing products to see them here.</p>
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
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-lg mb-2 line-clamp-2"><?= escape($product['name']) ?></h3>
                            <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?= escape($product['short_description'] ?? '') ?></p>
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-blue-600">$<?= number_format((float)($product['price'] ?? 0), 2) ?></span>
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

<?php include __DIR__ . '/includes/footer.php'; ?>

