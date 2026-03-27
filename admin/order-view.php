<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Order;

if (empty($_GET['id'])) {
    header('Location: ' . url('admin/orders.php'));
    exit;
}

$orderModel = new Order();
$order = $orderModel->getById($_GET['id']);

if (!$order) {
    header('Location: ' . url('admin/orders.php'));
    exit;
}

$message = '';
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['update_status'])) {
    $status = $_POST['status'];
    if (in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
        $orderModel->update($order['id'], ['status' => $status]);
        $message = 'Order status updated successfully.';
        // Reload order
        $order = $orderModel->getById($_GET['id']);
    }
}

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['update_payment_status'])) {
    $paymentStatus = $_POST['payment_status'];
    if (in_array($paymentStatus, ['pending', 'paid', 'failed', 'refunded'])) {
        $orderModel->update($order['id'], ['payment_status' => $paymentStatus]);
        $message = 'Payment status updated successfully.';
        // Reload order
        $order = $orderModel->getById($_GET['id']);
    }
}

// Handle notes update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notes'])) {
    $orderModel->update($order['id'], ['notes' => $_POST['notes']]);
    $message = 'Order notes updated successfully.';
    // Reload order
    $order = $orderModel->getById($_GET['id']);
}

$pageTitle = 'Order #' . escape($order['order_number']);
include __DIR__ . '/includes/header.php';
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Order #<?= escape($order['order_number']) ?></h1>
            <p class="text-gray-600 mt-1">
                Placed on <?= date('F d, Y \a\t g:i A', strtotime($order['created_at'])) ?>
            </p>
        </div>
        <a href="<?= url('admin/orders.php') ?>" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Back to Orders
        </a>
    </div>
    
    <?php if (!empty($message)): ?>
    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
        <?= escape($message) ?>
    </div>
    <?php endif; ?>
    
    <div class="grid md:grid-cols-3 gap-6 mb-6">
        <!-- Order Status -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-bold text-lg mb-4">Order Status</h3>
            <?php
            $statusColors = [
                'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                'processing' => 'bg-blue-100 text-blue-800 border-blue-300',
                'shipped' => 'bg-purple-100 text-purple-800 border-purple-300',
                'delivered' => 'bg-green-100 text-green-800 border-green-300',
                'cancelled' => 'bg-red-100 text-red-800 border-red-300'
            ];
            $statusColor = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800 border-gray-300';
            ?>
            <div class="mb-4">
                <span class="px-4 py-2 rounded-lg border-2 font-semibold <?= $statusColor ?>">
                    <?= ucfirst($order['status']) ?>
                </span>
            </div>
            <form method="POST" class="mb-2">
                <select name="status" class="w-full px-4 py-2 border rounded-lg mb-2">
                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <button type="submit" name="update_status" class="btn-primary-sm w-full">
                    <i class="fas fa-save mr-2"></i> Update Status
                </button>
            </form>
        </div>
        
        <!-- Payment Status -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-bold text-lg mb-4">Payment Status</h3>
            <?php
            $paymentColors = [
                'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                'paid' => 'bg-green-100 text-green-800 border-green-300',
                'failed' => 'bg-red-100 text-red-800 border-red-300',
                'refunded' => 'bg-gray-100 text-gray-800 border-gray-300'
            ];
            $paymentColor = $paymentColors[$order['payment_status']] ?? 'bg-gray-100 text-gray-800 border-gray-300';
            ?>
            <div class="mb-4">
                <span class="px-4 py-2 rounded-lg border-2 font-semibold <?= $paymentColor ?>">
                    <?= ucfirst($order['payment_status']) ?>
                </span>
            </div>
            <form method="POST" class="mb-2">
                <select name="payment_status" class="w-full px-4 py-2 border rounded-lg mb-2">
                    <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="failed" <?= $order['payment_status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                    <option value="refunded" <?= $order['payment_status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                </select>
                <button type="submit" name="update_payment_status" class="btn-primary-sm w-full">
                    <i class="fas fa-save mr-2"></i> Update Payment Status
                </button>
            </form>
        </div>
        
        <!-- Order Summary -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-bold text-lg mb-4">Order Summary</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Subtotal:</span>
                    <span class="font-semibold">$<?= number_format($order['subtotal'], 2) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Tax:</span>
                    <span class="font-semibold">$<?= number_format($order['tax'] ?? 0, 2) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Shipping:</span>
                    <span class="font-semibold">$<?= number_format($order['shipping'] ?? 0, 2) ?></span>
                </div>
                <div class="border-t pt-2 flex justify-between">
                    <span class="font-bold text-lg">Total:</span>
                    <span class="font-bold text-lg text-blue-600">$<?= number_format($order['total'], 2) ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Customer Information -->
    <div class="grid md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-bold text-lg mb-4">
                <i class="fas fa-user mr-2"></i>
                Customer Information
            </h3>
            <?php if ($order['customer_id']): ?>
                <div class="space-y-2">
                    <p><strong>Name:</strong> <?= escape($order['first_name'] . ' ' . $order['last_name']) ?></p>
                    <p><strong>Email:</strong> <a href="mailto:<?= escape($order['customer_email']) ?>" class="text-blue-600 hover:underline"><?= escape($order['customer_email']) ?></a></p>
                    <?php if ($order['customer_phone']): ?>
                    <p><strong>Phone:</strong> <a href="tel:<?= escape($order['customer_phone']) ?>" class="text-blue-600 hover:underline"><?= escape($order['customer_phone']) ?></a></p>
                    <?php endif; ?>
                    <?php if ($order['customer_company']): ?>
                    <p><strong>Company:</strong> <?= escape($order['customer_company']) ?></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500">Guest Customer</p>
                <p class="text-sm text-gray-400 mt-2">Session ID: <?= escape($order['session_id'] ?? 'N/A') ?></p>
            <?php endif; ?>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-bold text-lg mb-4">
                <i class="fas fa-truck mr-2"></i>
                Shipping Address
            </h3>
            <div class="whitespace-pre-line text-gray-700">
                <?= escape($order['shipping_address'] ?? 'No shipping address provided') ?>
            </div>
        </div>
    </div>
    
    <?php if ($order['billing_address']): ?>
    <div class="mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-bold text-lg mb-4">
                <i class="fas fa-credit-card mr-2"></i>
                Billing Address
            </h3>
            <div class="whitespace-pre-line text-gray-700">
                <?= escape($order['billing_address']) ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Order Items -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="font-bold text-lg mb-4">
            <i class="fas fa-box mr-2"></i>
            Order Items
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left">Product</th>
                        <th class="px-4 py-3 text-left">SKU</th>
                        <th class="px-4 py-3 text-center">Quantity</th>
                        <th class="px-4 py-3 text-right">Price</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order['items'] ?? [] as $item): ?>
                    <tr class="border-t">
                        <td class="px-4 py-3">
                            <?php if (!empty($item['product_slug'])): ?>
                                <a href="<?= url('product.php?slug=' . escape($item['product_slug'])) ?>" target="_blank" class="text-blue-600 hover:underline">
                                    <?= escape($item['product_name']) ?>
                                </a>
                            <?php else: ?>
                                <?= escape($item['product_name']) ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <?= escape($item['product_sku'] ?? 'N/A') ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?= escape($item['quantity']) ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            $<?= number_format($item['price'], 2) ?>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold">
                            $<?= number_format($item['subtotal'], 2) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Order Notes -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="font-bold text-lg mb-4">
            <i class="fas fa-sticky-note mr-2"></i>
            Order Notes
        </h3>
        <form method="POST">
            <textarea name="notes" 
                      rows="4" 
                      class="w-full px-4 py-2 border rounded-lg mb-3"
                      placeholder="Add internal notes about this order..."><?= escape($order['notes'] ?? '') ?></textarea>
            <button type="submit" class="btn-primary-sm">
                <i class="fas fa-save mr-2"></i> Save Notes
            </button>
        </form>
    </div>
    
    <!-- Actions -->
    <div class="flex gap-4">
        <button onclick="window.print()" class="btn-secondary">
            <i class="fas fa-print mr-2"></i> Print Order
        </button>
        <a href="?id=<?= $order['id'] ?>&delete=1" 
           onclick="return confirm('Are you sure you want to delete this order? This action cannot be undone.')"
           class="btn-secondary bg-red-600 hover:bg-red-700 text-white">
            <i class="fas fa-trash mr-2"></i> Delete Order
        </a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

