<?php
/**
 * Guest Checkout - No Account Required
 */
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
    header('Location: ' . url('cart.php'));
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
        $orderNumber = 'ORD-' . strtoupper(uniqid());
        
        try {
            // Create order as quote request for now
            db()->insert('quote_requests', [
                'name' => $orderData['name'],
                'email' => $orderData['email'],
                'phone' => $orderData['phone'],
                'company' => $orderData['company'],
                'message' => "Guest Order Request #{$orderNumber}\n\n" . 
                           "Items:\n" . 
                           implode("\n", array_map(fn($p) => "{$p['name']} x{$p['cart_quantity']} - $" . number_format($p['line_total'], 2), $products)) .
                           "\n\nShipping Address:\n{$orderData['shipping_address']}\n{$orderData['city']}, {$orderData['state']} {$orderData['zip_code']}" .
                           ($orderData['notes'] ? "\n\nNotes: {$orderData['notes']}" : ''),
                'status' => 'pending'
            ]);
            
            // Clear cart
            $_SESSION['cart'] = [];
            
            // Store order number in session for tracking
            $_SESSION['last_order_number'] = $orderNumber;
            
            $message = "Order request submitted successfully! Order #: {$orderNumber}";
        } catch (Exception $e) {
            $error = 'Error submitting order. Please try again.';
        }
    }
}

$pageTitle = 'Checkout - ' . get_site_name();
$robotsNoIndex = true;
include __DIR__ . '/includes/header.php';
?>

<main class="py-8">
    <div class="container mx-auto px-4 max-w-5xl">
        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">1</div>
                    <span class="ml-2 font-semibold">Cart</span>
                </div>
                <div class="w-16 h-1 bg-blue-600 mx-4"></div>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">2</div>
                    <span class="ml-2 font-semibold">Checkout</span>
                </div>
                <div class="w-16 h-1 bg-gray-300 mx-4"></div>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center font-bold">3</div>
                    <span class="ml-2 text-gray-600">Confirmation</span>
                </div>
            </div>
        </div>
        
        <h1 class="text-3xl font-bold mb-6">Checkout</h1>
        
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg mb-6">
                <h2 class="text-xl font-bold mb-2"><?= escape($message) ?></h2>
                <p class="mb-4">We will contact you shortly to confirm your order and arrange payment.</p>
                <?php if (isset($_SESSION['last_order_number'])): ?>
                    <div class="bg-white p-4 rounded border border-green-300">
                        <p class="font-semibold">Your Order Number:</p>
                        <p class="text-2xl font-bold text-green-700"><?= escape($_SESSION['last_order_number']) ?></p>
                        <a href="<?= url('order-tracking.php?order=' . escape($_SESSION['last_order_number'])) ?>" class="text-blue-600 hover:underline mt-2 inline-block">
                            Track Your Order →
                        </a>
                    </div>
                <?php endif; ?>
                <div class="mt-4">
                    <a href="<?= url('products.php') ?>" class="btn-primary inline-block mr-4">Continue Shopping</a>
                    <?php if (!isset($_SESSION['customer_id'])): ?>
                        <a href="<?= url('register.php') ?>" class="btn-secondary inline-block">Create Account for Faster Checkout</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Checkout Form -->
                <div class="md:col-span-2">
                    <form method="POST" class="space-y-6">
                        <!-- Contact Information -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-bold mb-4 flex items-center">
                                <i class="fas fa-user mr-2 text-blue-600"></i>
                                Contact Information
                            </h2>
                            
                            <?php if ($error): ?>
                                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                                    <?= escape($error) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">Full Name *</label>
                                    <input type="text" name="name" required value="<?= escape($_POST['name'] ?? '') ?>"
                                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium mb-2">Email *</label>
                                    <input type="email" name="email" required value="<?= escape($_POST['email'] ?? '') ?>"
                                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium mb-2">Phone *</label>
                                    <input type="tel" name="phone" required value="<?= escape($_POST['phone'] ?? '') ?>"
                                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium mb-2">Company</label>
                                    <input type="text" name="company" value="<?= escape($_POST['company'] ?? '') ?>"
                                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Shipping Address -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-bold mb-4 flex items-center">
                                <i class="fas fa-truck mr-2 text-blue-600"></i>
                                Shipping Address
                            </h2>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">Address *</label>
                                    <textarea name="shipping_address" rows="3" required
                                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"><?= escape($_POST['shipping_address'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="grid md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium mb-2">City *</label>
                                        <input type="text" name="city" required value="<?= escape($_POST['city'] ?? '') ?>"
                                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium mb-2">State *</label>
                                        <input type="text" name="state" required value="<?= escape($_POST['state'] ?? '') ?>"
                                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium mb-2">ZIP Code *</label>
                                        <input type="text" name="zip_code" required value="<?= escape($_POST['zip_code'] ?? '') ?>"
                                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium mb-2">Country</label>
                                    <input type="text" name="country" value="<?= escape($_POST['country'] ?? 'USA') ?>"
                                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Notes -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-bold mb-4">Additional Notes</h2>
                            <textarea name="notes" rows="3"
                                      placeholder="Special delivery instructions or notes..."
                                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"><?= escape($_POST['notes'] ?? '') ?></textarea>
                        </div>
                        
                        <!-- Continue Button -->
                        <div class="flex gap-4">
                            <a href="<?= url('cart.php') ?>" class="btn-secondary flex-1 text-center">Back to Cart</a>
                            <button type="submit" class="btn-primary flex-1">
                                <i class="fas fa-lock mr-2"></i>
                                Complete Order
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Order Summary -->
                <div class="bg-white rounded-lg shadow-md p-6 h-fit sticky top-20">
                    <h2 class="text-xl font-bold mb-4">Order Summary</h2>
                    
                    <div class="space-y-3 mb-4 max-h-64 overflow-y-auto">
                        <?php foreach ($products as $product): ?>
                        <div class="flex gap-3 border-b pb-3">
                            <div class="w-16 h-16 bg-gray-200 rounded overflow-hidden flex-shrink-0">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?= asset('storage/uploads/' . escape($product['image'])) ?>" 
                                         alt="<?= escape($product['name']) ?>" 
                                         class="w-full h-full object-cover">
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-sm line-clamp-2"><?= escape($product['name']) ?></p>
                                <p class="text-xs text-gray-600">Qty: <?= $product['cart_quantity'] ?></p>
                                <p class="text-sm font-bold text-blue-600">$<?= number_format($product['line_total'], 2) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="border-t pt-3 space-y-2 mb-4">
                        <div class="flex justify-between text-sm">
                            <span>Subtotal:</span>
                            <span class="font-semibold">$<?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Tax (8%):</span>
                            <span>$<?= number_format($tax, 2) ?></span>
                        </div>
                        <div class="border-t pt-2 flex justify-between text-lg font-bold">
                            <span>Total:</span>
                            <span class="text-blue-600">$<?= number_format($total, 2) ?></span>
                        </div>
                    </div>
                    
                    <?php if (!isset($_SESSION['customer_id'])): ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                            <p class="text-sm text-blue-800 mb-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Create an account for faster checkout next time!
                            </p>
                            <a href="<?= url('register.php') ?>" class="text-blue-600 hover:underline text-sm font-semibold">
                                Sign Up →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

