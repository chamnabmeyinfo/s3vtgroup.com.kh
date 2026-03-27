<?php
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Product;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$productModel = new Product();
$cartItems = $_SESSION['cart'] ?? [];
$products = [];
$total = 0;

foreach ($cartItems as $productId => $quantity) {
    $product = $productModel->getById($productId);
    if ($product && $product['is_active']) {
        $product['cart_quantity'] = $quantity;
        $product['line_total'] = ($product['sale_price'] ?? $product['price']) * $quantity;
        $total += $product['line_total'];
        $products[] = $product;
    }
}

$pageTitle = 'Shopping Cart - ' . get_site_name();
$robotsNoIndex = true;
include __DIR__ . '/includes/header.php';
?>

<main class="py-8 md:py-12 bg-gradient-to-br from-gray-50 to-white">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold mb-2 text-gray-800">
                Shopping Cart
            </h1>
            <p class="text-gray-600">Review your items and proceed to checkout</p>
        </div>
        
        <?php if (empty($products)): ?>
            <div class="bg-white rounded-2xl shadow-xl p-12 md:p-16 text-center border border-gray-100">
                <div class="w-32 h-32 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-shopping-cart text-6xl text-blue-500"></i>
                </div>
                <h2 class="text-3xl font-bold mb-3 text-gray-800">Your Cart is Empty</h2>
                <p class="text-gray-600 mb-8 text-lg">Start adding products to your cart!</p>
                <a href="<?= url('products.php') ?>" class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-4 rounded-xl font-bold hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl inline-flex items-center">
                    <i class="fas fa-box mr-2"></i>Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="grid lg:grid-cols-3 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-2 space-y-4">
                    <?php foreach ($products as $product): ?>
                    <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 border border-gray-100 group">
                        <div class="flex gap-6">
                            <div class="w-32 h-32 md:w-40 md:h-40 flex-shrink-0 bg-gray-100 rounded-xl overflow-hidden group-hover:scale-105 transition-transform duration-300">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?= asset('storage/uploads/' . escape($product['image'])) ?>" 
                                         alt="<?= escape($product['name']) ?>" 
                                         class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fas fa-image text-4xl text-gray-300"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg md:text-xl font-bold mb-2 group-hover:text-blue-600 transition-colors">
                                    <a href="<?= url('product.php?slug=' . escape($product['slug'])) ?>" 
                                       class="hover:underline">
                                        <?= escape($product['name']) ?>
                                    </a>
                                </h3>
                                <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?= escape($product['short_description'] ?? '') ?></p>
                                
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <p class="text-2xl font-bold text-blue-600 mb-1">
                                            $<?= number_format($product['sale_price'] ?? $product['price'], 2) ?>
                                        </p>
                                        <p class="text-sm text-gray-500">Subtotal: <span class="font-semibold">$<?= number_format($product['line_total'], 2) ?></span></p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center border-2 border-gray-200 rounded-xl overflow-hidden">
                                            <button onclick="updateQuantity(<?= $product['id'] ?>, <?= $product['cart_quantity'] - 1 ?>)" 
                                                    class="px-4 py-2 hover:bg-gray-100 transition-colors font-bold text-gray-600">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" 
                                                   id="qty-<?= $product['id'] ?>" 
                                                   value="<?= $product['cart_quantity'] ?>" 
                                                   min="1"
                                                   onchange="updateQuantity(<?= $product['id'] ?>, this.value)"
                                                   class="w-16 text-center border-0 focus:outline-none font-semibold">
                                            <button onclick="updateQuantity(<?= $product['id'] ?>, <?= $product['cart_quantity'] + 1 ?>)" 
                                                    class="px-4 py-2 hover:bg-gray-100 transition-colors font-bold text-gray-600">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <button onclick="removeFromCart(<?= $product['id'] ?>)" 
                                                class="w-12 h-12 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl flex items-center justify-center transition-all duration-300 transform hover:scale-110">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Cart Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-xl p-6 border border-gray-100 sticky top-24">
                        <h2 class="text-2xl font-bold mb-6 flex items-center">
                            <i class="fas fa-receipt mr-2 text-blue-600"></i>Cart Summary
                        </h2>
                        <div class="space-y-4 mb-6">
                            <div class="flex justify-between items-center py-2">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-bold text-lg">$<?= number_format($total, 2) ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 text-gray-600 border-t border-gray-200">
                                <span>Tax (estimated):</span>
                                <span>$<?= number_format($total * 0.08, 2) ?></span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-t-2 border-gray-300">
                                <span class="text-xl font-bold">Total:</span>
                                <span class="text-2xl font-bold text-blue-600">$<?= number_format($total * 1.08, 2) ?></span>
                            </div>
                        </div>
                        
                        <a href="<?= url('checkout-guest.php') ?>" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-4 rounded-xl font-bold hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl text-center block mb-3 flex items-center justify-center">
                            <i class="fas fa-arrow-right mr-2"></i>Proceed to Checkout
                        </a>
                        
                        <?php if (!isset($_SESSION['customer_id'])): ?>
                            <div class="text-center mb-3">
                                <span class="text-xs text-gray-500">or</span>
                            </div>
                            <a href="<?= url('register.php') ?>" class="w-full bg-white border-2 border-blue-600 text-blue-600 px-6 py-3 rounded-xl font-semibold hover:bg-blue-50 transition-all duration-300 text-center block mb-3">
                                <i class="fas fa-user-plus mr-2"></i>Create Account
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?= url('products.php') ?>" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-800 px-6 py-3 rounded-xl font-semibold transition-all duration-300 text-center block flex items-center justify-center">
                            <i class="fas fa-arrow-left mr-2"></i>Continue Shopping
                        </a>
                        
                        <!-- Security Badge -->
                        <div class="mt-6 pt-6 border-t border-gray-200 flex items-center justify-center gap-2 text-sm text-gray-600">
                            <i class="fas fa-lock text-green-500"></i>
                            <span>Secure Checkout</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
function updateQuantity(productId, quantity) {
    if (quantity < 1) {
        removeFromCart(productId);
        return;
    }
    
    fetch('<?= url('api/cart.php') ?>?action=update&product_id=' + productId + '&quantity=' + quantity, {
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
    .then(() => location.reload())
    .catch(error => {
        console.error('Error updating quantity:', error);
        alert('Error updating quantity. Please try again.');
    });
}

function removeFromCart(productId) {
    if (confirm('Remove this item from cart?')) {
        fetch('<?= url('api/cart.php') ?>?action=remove&product_id=' + productId, {
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
        .then(() => location.reload())
        .catch(error => {
            console.error('Error removing item:', error);
            alert('Error removing item. Please try again.');
        });
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

