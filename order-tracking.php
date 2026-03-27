<?php
/**
 * Order Tracking Page
 */
require_once __DIR__ . '/bootstrap/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$orderNumber = trim($_GET['order'] ?? '');
$order = null;
$orderItems = [];

if ($orderNumber) {
    // Search in quote_requests for now (since we're using that for orders)
    $order = db()->fetchOne(
        "SELECT * FROM quote_requests WHERE message LIKE :order ORDER BY created_at DESC LIMIT 1",
        ['order' => "%{$orderNumber}%"]
    );
    
    if ($order) {
        // Parse order items from message (simplified)
        // In a real app, this would come from order_items table
    }
}

$pageTitle = 'Order Tracking - ' . get_site_name();
include __DIR__ . '/includes/header.php';
?>

<main class="py-12">
    <div class="container mx-auto px-4 max-w-4xl">
        <h1 class="text-3xl font-bold mb-8">Track Your Order</h1>
        
        <div class="bg-white rounded-lg shadow-md p-8">
            <form method="GET" class="mb-8">
                <div class="flex gap-4">
                    <input type="text" 
                           name="order" 
                           value="<?= escape($orderNumber) ?>"
                           placeholder="Enter your order number (e.g., ORD-123456)"
                           class="flex-1 px-4 py-3 border rounded-lg text-lg focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="btn-primary px-8">
                        <i class="fas fa-search mr-2"></i>
                        Track Order
                    </button>
                </div>
            </form>
            
            <?php if ($orderNumber && !$order): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                    <i class="fas fa-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
                    <h2 class="text-xl font-bold mb-2">Order Not Found</h2>
                    <p class="text-gray-600">We couldn't find an order with that number. Please check and try again.</p>
                </div>
            <?php elseif ($order): ?>
                
                <!-- Order Status Timeline -->
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold">Order Status</h2>
                        <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-lg font-semibold">
                            <?= ucfirst($order['status'] ?? 'Pending') ?>
                        </span>
                    </div>
                    
                    <div class="relative">
                        <!-- Timeline -->
                        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                        
                        <?php
                        $statuses = [
                            ['status' => 'pending', 'label' => 'Order Placed', 'icon' => 'fa-shopping-cart', 'date' => date('M d, Y', strtotime($order['created_at']))],
                            ['status' => 'processing', 'label' => 'Processing', 'icon' => 'fa-cog', 'date' => ''],
                            ['status' => 'shipped', 'label' => 'Shipped', 'icon' => 'fa-truck', 'date' => ''],
                            ['status' => 'delivered', 'label' => 'Delivered', 'icon' => 'fa-check-circle', 'date' => ''],
                        ];
                        
                        $currentStatus = $order['status'] ?? 'pending';
                        $currentIndex = array_search($currentStatus, array_column($statuses, 'status'));
                        if ($currentIndex === false) $currentIndex = 0;
                        ?>
                        
                        <?php foreach ($statuses as $index => $status): ?>
                            <div class="relative flex items-start mb-6">
                                <div class="relative z-10 flex items-center justify-center w-8 h-8 rounded-full <?= 
                                    $index <= $currentIndex ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-400'
                                ?>">
                                    <i class="fas <?= $status['icon'] ?> text-sm"></i>
                                </div>
                                <div class="ml-6 flex-1">
                                    <h3 class="font-semibold <?= $index <= $currentIndex ? 'text-gray-900' : 'text-gray-400' ?>">
                                        <?= $status['label'] ?>
                                    </h3>
                                    <?php if ($status['date']): ?>
                                        <p class="text-sm text-gray-600"><?= $status['date'] ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Order Details -->
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="font-bold mb-4">Order Information</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Order Number:</span>
                                <span class="font-semibold"><?= escape($orderNumber) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Order Date:</span>
                                <span><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span class="font-semibold"><?= ucfirst($order['status'] ?? 'Pending') ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="font-bold mb-4">Contact Information</h3>
                        <div class="space-y-2 text-sm">
                            <p><strong>Name:</strong> <?= escape($order['name']) ?></p>
                            <p><strong>Email:</strong> <?= escape($order['email']) ?></p>
                            <?php if (!empty($order['phone'])): ?>
                                <p><strong>Phone:</strong> <?= escape($order['phone']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Support -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
                    <h3 class="font-bold mb-2">Need Help?</h3>
                    <p class="text-gray-600 mb-4">If you have questions about your order, our support team is here to help.</p>
                    <a href="<?= url('contact.php') ?>" class="btn-primary inline-block">
                        Contact Support
                    </a>
                </div>
                
            <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-box text-6xl text-gray-300 mb-4"></i>
                    <h2 class="text-xl font-bold mb-2">Enter Your Order Number</h2>
                    <p class="text-gray-600">Enter your order number above to track your order status.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

