<?php
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Product;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$productModel = new Product();
$cartItems = $_SESSION['cart'] ?? [];
$products = [];
$subtotal = 0;
$taxRate = 0.08;
$message = '';
$error = '';

if (empty($cartItems)) {
    header('Location: ' . url('checkout-guest.php'));
    exit;
}

foreach ($cartItems as $productId => $quantity) {
    $product = $productModel->getById($productId);
    if ($product && $product['is_active']) {
        $product['cart_quantity'] = $quantity;
        $product['line_total'] = ($product['sale_price'] ?? $product['price']) * $quantity;
        $subtotal += $product['line_total'];
        $products[] = $product;
    }
}

$tax = $subtotal * $taxRate;
$total = $subtotal + $tax;

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Security: CSRF protection
    require_csrf();
    
    $orderData = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'company' => trim($_POST['company'] ?? ''),
        'shipping_address' => trim($_POST['shipping_address'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'state' => trim($_POST['state'] ?? ''),
        'zip_code' => trim($_POST['zip_code'] ?? ''),
        'country' => trim($_POST['country'] ?? 'USA'),
        'notes' => trim($_POST['notes'] ?? ''),
    ];
    
    if (empty($orderData['name']) || empty($orderData['email'])) {
        $error = 'Please fill in all required fields.';
    } else {
        // Create order (simplified - in real app would use orders table)
        $orderNumber = 'ORD-' . strtoupper(uniqid());
        
        // Save order to quote_requests for now (or create proper order system)
        try {
            db()->insert('quote_requests', [
                'name' => $orderData['name'],
                'email' => $orderData['email'],
                'phone' => $orderData['phone'],
                'company' => $orderData['company'],
                'message' => "Order Request #{$orderNumber}\n\n" . 
                           "Items:\n" . 
                           implode("\n", array_map(fn($p) => "{$p['name']} x{$p['cart_quantity']} - $" . number_format($p['line_total'], 2), $products)) .
                           "\n\nShipping Address:\n{$orderData['shipping_address']}\n{$orderData['city']}, {$orderData['state']} {$orderData['zip_code']}" .
                           ($orderData['notes'] ? "\n\nNotes: {$orderData['notes']}" : ''),
                'status' => 'pending'
            ]);
            
            // Clear cart
            $_SESSION['cart'] = [];
            
            $message = "Order request submitted successfully! Order #: {$orderNumber}";
        } catch (Exception $e) {
            $error = 'Error submitting order. Please try again.';
        }
    }
}

$pageTitle = 'Checkout - ' . get_site_name();
$robotsNoIndex = true;
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/message.php';
?>

<main class="py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <h1 class="text-3xl font-bold mb-6">Checkout</h1>
        
        <?php if ($message): ?>
            <div class="message-alert bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 rounded-lg shadow-lg p-4 mb-6 transform transition-all duration-500 ease-out">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3 flex-1">
                        <div class="flex-shrink-0 mt-0.5">
                            <i class="fas fa-check-circle text-2xl text-green-600 animate-pulse"></i>
                        </div>
                        <div class="flex-1 text-green-800">
                            <p class="font-semibold text-base leading-relaxed"><?= escape($message) ?></p>
                            <p class="mt-2 text-sm">We will contact you shortly to confirm your order and arrange payment.</p>
                            <a href="<?= url('products.php') ?>" class="btn-primary mt-4 inline-block">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            
            <form method="POST" class="grid md:grid-cols-3 gap-8">
                <?= csrf_field() ?>
                <!-- Order Details -->
                <div class="md:col-span-2 space-y-6">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold mb-4">Shipping Information</h2>
                        
                        <?= displayMessage('', $error) ?>
                        
                        <div class="space-y-4">
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">Full Name *</label>
                                    <input type="text" name="name" required value="<?= escape($_POST['name'] ?? '') ?>"
                                           class="w-full px-4 py-2 border rounded-lg">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium mb-2">Company</label>
                                    <input type="text" name="company" value="<?= escape($_POST['company'] ?? '') ?>"
                                           class="w-full px-4 py-2 border rounded-lg">
                                </div>
                            </div>
                            
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">Email *</label>
                                    <input type="email" name="email" required value="<?= escape($_POST['email'] ?? '') ?>"
                                           class="w-full px-4 py-2 border rounded-lg">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium mb-2">Phone *</label>
                                    <input type="tel" name="phone" required value="<?= escape($_POST['phone'] ?? '') ?>"
                                           class="w-full px-4 py-2 border rounded-lg">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">Shipping Address *</label>
                                <textarea name="shipping_address" rows="3" required
                                          class="w-full px-4 py-2 border rounded-lg"><?= escape($_POST['shipping_address'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="grid md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">City *</label>
                                    <input type="text" name="city" required value="<?= escape($_POST['city'] ?? '') ?>"
                                           class="w-full px-4 py-2 border rounded-lg">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium mb-2">State *</label>
                                    <input type="text" name="state" required value="<?= escape($_POST['state'] ?? '') ?>"
                                           class="w-full px-4 py-2 border rounded-lg">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium mb-2">ZIP Code *</label>
                                    <input type="text" name="zip_code" required value="<?= escape($_POST['zip_code'] ?? '') ?>"
                                           class="w-full px-4 py-2 border rounded-lg">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">Country</label>
                                <input type="text" name="country" value="<?= escape($_POST['country'] ?? 'USA') ?>"
                                       class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">Order Notes</label>
                                <textarea name="notes" rows="3"
                                          class="w-full px-4 py-2 border rounded-lg"><?= escape($_POST['notes'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="bg-white rounded-lg shadow-md p-6 h-fit sticky top-20">
                    <h2 class="text-xl font-bold mb-4">Order Summary</h2>
                    
                    <div class="space-y-3 mb-4">
                        <?php foreach ($products as $product): ?>
                        <div class="flex justify-between text-sm">
                            <span><?= escape($product['name']) ?> x<?= $product['cart_quantity'] ?></span>
                            <span>$<?= number_format($product['line_total'], 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="border-t pt-3 space-y-2 mb-4">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span>$<?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Tax (8%):</span>
                            <span>$<?= number_format($tax, 2) ?></span>
                        </div>
                        <div class="border-t pt-2 flex justify-between text-lg font-bold">
                            <span>Total:</span>
                            <span class="text-blue-600">$<?= number_format($total, 2) ?></span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary w-full mb-3">
                        Place Order
                    </button>
                    <a href="<?= url('cart.php') ?>" class="btn-secondary w-full text-center block">
                        Back to Cart
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

