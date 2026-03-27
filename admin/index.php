<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Product;
use App\Models\Category;

$productModel = new Product();
$categoryModel = new Category();

// Check if orders table exists
$hasOrders = false;
try {
    db()->fetchOne("SELECT 1 FROM orders LIMIT 1");
    $hasOrders = true;
} catch (Exception $e) {
    // Orders table doesn't exist
}

$stats = [
    'total_products' => db()->fetchOne("SELECT COUNT(*) as count FROM products WHERE is_active = 1")['count'],
    'total_products_all' => db()->fetchOne("SELECT COUNT(*) as count FROM products")['count'],
    'featured_products' => db()->fetchOne("SELECT COUNT(*) as count FROM products WHERE is_featured = 1 AND is_active = 1")['count'],
    'total_categories' => db()->fetchOne("SELECT COUNT(*) as count FROM categories WHERE is_active = 1")['count'],
    'pending_quotes' => db()->fetchOne("SELECT COUNT(*) as count FROM quote_requests WHERE status = 'pending'")['count'],
    'total_quotes' => db()->fetchOne("SELECT COUNT(*) as count FROM quote_requests")['count'],
    'unread_messages' => db()->fetchOne("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0")['count'],
    'total_messages' => db()->fetchOne("SELECT COUNT(*) as count FROM contact_messages")['count'],
    'quotes_today' => db()->fetchOne("SELECT COUNT(*) as count FROM quote_requests WHERE DATE(created_at) = CURDATE()")['count'],
    'messages_today' => db()->fetchOne("SELECT COUNT(*) as count FROM contact_messages WHERE DATE(created_at) = CURDATE()")['count'],
];

if ($hasOrders) {
    $stats['pending_orders'] = db()->fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'];
    $stats['total_orders'] = db()->fetchOne("SELECT COUNT(*) as count FROM orders")['count'];
    $stats['orders_today'] = db()->fetchOne("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()")['count'];
    $stats['orders_revenue'] = db()->fetchOne("SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE payment_status = 'paid'")['total'] ?? 0;
    
    // Get recent orders
    $recentOrders = db()->fetchAll(
        "SELECT o.*, 
         (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
         FROM orders o 
         ORDER BY o.created_at DESC LIMIT 5"
    );
}

$recentProducts = $productModel->getAll(['limit' => 5]);
$recentQuotes = db()->fetchAll(
    "SELECT q.*, p.name as product_name FROM quote_requests q 
     LEFT JOIN products p ON q.product_id = p.id 
     ORDER BY q.created_at DESC LIMIT 5"
);

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <!-- Welcome Header -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl shadow-xl p-4 md:p-6 lg:p-8 mb-4 md:mb-6 text-white">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-1 md:mb-2">
                    <i class="fas fa-tachometer-alt mr-2 md:mr-3"></i>
                    Dashboard
                </h1>
                <p class="text-blue-100 text-sm md:text-lg">Welcome back, <?= escape(session('admin_username') ?? 'Admin') ?>!</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-white/20 rounded-full px-4 md:px-6 py-2 md:py-3 backdrop-blur-sm">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="font-semibold text-sm md:text-base"><?= date('F j, Y') ?></span>
                    </div>
                </div>
                <button onclick="toggleEditMode()" id="editModeBtn" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all flex items-center gap-2">
                    <i class="fas fa-edit"></i>
                    <span class="hidden sm:inline">Edit Layout</span>
                </button>
                <button onclick="resetDashboard()" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all flex items-center gap-2">
                    <i class="fas fa-redo"></i>
                    <span class="hidden sm:inline">Reset</span>
                </button>
                <?php if (session('admin_role_slug') === 'super_admin'): ?>
                <a href="<?= url('developer/index.php') ?>" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 px-4 py-2 rounded-lg transition-all flex items-center gap-2 shadow-lg hover:shadow-xl transform hover:scale-105">
                    <i class="fas fa-code"></i>
                    <span class="hidden sm:inline">Developer</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Dashboard Grid Container -->
    <div id="dashboardGrid" class="dashboard-grid">
        <!-- Stats Widgets -->
        <div class="dashboard-widget" data-widget-id="products-stat" data-default-size="1x1">
            <div class="resize-handle resize-handle-top resize-handle-top"></div>
            <div class="resize-handle resize-handle-bottom resize-handle-bottom"></div>
            <div class="resize-handle resize-handle-left resize-handle-left"></div>
            <div class="resize-handle resize-handle-right resize-handle-right"></div>
            <div class="resize-handle resize-handle-corner resize-handle-top-left"></div>
            <div class="resize-handle resize-handle-corner resize-handle-top-right"></div>
            <div class="resize-handle resize-handle-corner resize-handle-bottom-left"></div>
            <div class="resize-handle resize-handle-corner resize-handle-bottom-right"></div>
            <div class="widget-header">
                <div class="widget-title">
                    <i class="fas fa-box mr-2"></i>
                    <span>Products</span>
                </div>
                <div class="widget-controls">
                    <button onclick="removeWidget('products-stat')" class="widget-btn-remove" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="widget-content">
                <a href="<?= url('admin/products.php') ?>" class="block h-full">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-4 md:p-6 text-white h-full flex flex-col justify-between transform transition-all duration-300 hover:scale-105">
                        <div class="flex items-center justify-between mb-3 md:mb-4">
                            <div class="bg-white/20 rounded-lg p-2 md:p-3 backdrop-blur-sm">
                                <i class="fas fa-box text-xl md:text-2xl"></i>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl md:text-3xl font-bold"><?= number_format($stats['total_products']) ?></div>
                                <div class="text-blue-100 text-xs md:text-sm">Active</div>
                            </div>
                        </div>
                        <div class="text-blue-100 text-xs md:text-sm font-medium mb-1">Products</div>
                        <div class="text-blue-200 text-xs"><?= $stats['featured_products'] ?> featured • <?= $stats['total_products_all'] ?> total</div>
                        <div class="mt-3 md:mt-4 inline-flex items-center justify-center bg-white/20 hover:bg-white/30 px-3 md:px-4 py-2 rounded-lg transition-all text-xs md:text-sm">
                            View All <i class="fas fa-arrow-right ml-1 text-xs"></i>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="dashboard-widget" data-widget-id="categories-stat" data-default-size="1x1">
            <div class="resize-handle resize-handle-top"></div>
            <div class="resize-handle resize-handle-bottom"></div>
            <div class="resize-handle resize-handle-left"></div>
            <div class="resize-handle resize-handle-right"></div>
            <div class="resize-handle resize-handle-corner resize-handle-top-left"></div>
            <div class="resize-handle resize-handle-corner resize-handle-top-right"></div>
            <div class="resize-handle resize-handle-corner resize-handle-bottom-left"></div>
            <div class="resize-handle resize-handle-corner resize-handle-bottom-right"></div>
            <div class="widget-header">
                <div class="widget-title">
                    <i class="fas fa-tags mr-2"></i>
                    <span>Categories</span>
                </div>
                <div class="widget-controls">
                    <button onclick="removeWidget('categories-stat')" class="widget-btn-remove" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="widget-content">
                <a href="<?= url('admin/categories.php') ?>" class="block h-full">
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-4 md:p-6 text-white h-full flex flex-col justify-between transform transition-all duration-300 hover:scale-105">
                        <div class="flex items-center justify-between mb-3 md:mb-4">
                            <div class="bg-white/20 rounded-lg p-2 md:p-3 backdrop-blur-sm">
                                <i class="fas fa-tags text-xl md:text-2xl"></i>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl md:text-3xl font-bold"><?= number_format($stats['total_categories']) ?></div>
                                <div class="text-green-100 text-xs md:text-sm">Categories</div>
                            </div>
                        </div>
                        <div class="text-green-100 text-xs md:text-sm font-medium">Product Categories</div>
                        <div class="mt-3 md:mt-4 inline-flex items-center justify-center bg-white/20 hover:bg-white/30 px-3 md:px-4 py-2 rounded-lg transition-all text-xs md:text-sm">
                            View All <i class="fas fa-arrow-right ml-1 text-xs"></i>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="dashboard-widget" data-widget-id="quotes-stat" data-default-size="1x1">
            <div class="widget-header">
                <div class="widget-title">
                    <i class="fas fa-calculator mr-2"></i>
                    <span>Quote Requests</span>
                </div>
                <div class="widget-controls">
                    <button onclick="removeWidget('quotes-stat')" class="widget-btn-remove" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="widget-content">
                <a href="<?= url('admin/quotes.php') ?>" class="block h-full">
                    <div class="bg-gradient-to-br from-yellow-500 to-amber-600 rounded-xl shadow-lg p-4 md:p-6 text-white h-full flex flex-col justify-between transform transition-all duration-300 hover:scale-105">
                        <div class="flex items-center justify-between mb-3 md:mb-4">
                            <div class="bg-white/20 rounded-lg p-2 md:p-3 backdrop-blur-sm">
                                <i class="fas fa-calculator text-xl md:text-2xl"></i>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl md:text-3xl font-bold"><?= number_format($stats['pending_quotes']) ?></div>
                                <div class="text-yellow-100 text-xs md:text-sm">Pending</div>
                            </div>
                        </div>
                        <div class="text-yellow-100 text-xs md:text-sm font-medium mb-1">Quote Requests</div>
                        <div class="text-yellow-200 text-xs"><?= $stats['quotes_today'] ?> today • <?= $stats['total_quotes'] ?> total</div>
                        <div class="mt-3 md:mt-4 inline-flex items-center justify-center bg-white/20 hover:bg-white/30 px-3 md:px-4 py-2 rounded-lg transition-all text-xs md:text-sm">
                            View All <i class="fas fa-arrow-right ml-1 text-xs"></i>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <?php if ($hasOrders): ?>
        <div class="dashboard-widget" data-widget-id="orders-stat" data-default-size="1x1">
            <div class="widget-header">
                <div class="widget-title">
                    <i class="fas fa-shopping-cart mr-2"></i>
                    <span>Orders</span>
                </div>
                <div class="widget-controls">
                    <button onclick="removeWidget('orders-stat')" class="widget-btn-remove" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="widget-content">
                <a href="<?= url('admin/orders.php') ?>" class="block h-full">
                    <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl shadow-lg p-4 md:p-6 text-white h-full flex flex-col justify-between transform transition-all duration-300 hover:scale-105">
                        <div class="flex items-center justify-between mb-3 md:mb-4">
                            <div class="bg-white/20 rounded-lg p-2 md:p-3 backdrop-blur-sm">
                                <i class="fas fa-shopping-cart text-xl md:text-2xl"></i>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl md:text-3xl font-bold"><?= number_format($stats['pending_orders']) ?></div>
                                <div class="text-purple-100 text-xs md:text-sm">Pending</div>
                            </div>
                        </div>
                        <div class="text-purple-100 text-xs md:text-sm font-medium mb-1">Orders</div>
                        <div class="text-purple-200 text-xs"><?= $stats['orders_today'] ?> today • <?= $stats['total_orders'] ?> total</div>
                        <div class="mt-3 md:mt-4 inline-flex items-center justify-center bg-white/20 hover:bg-white/30 px-3 md:px-4 py-2 rounded-lg transition-all text-xs md:text-sm">
                            View All <i class="fas fa-arrow-right ml-1 text-xs"></i>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="dashboard-widget" data-widget-id="messages-stat" data-default-size="1x1">
            <div class="widget-header">
                <div class="widget-title">
                    <i class="fas fa-envelope mr-2"></i>
                    <span>Messages</span>
                </div>
                <div class="widget-controls">
                    <button onclick="removeWidget('messages-stat')" class="widget-btn-remove" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="widget-content">
                <a href="<?= url('admin/messages.php') ?>" class="block h-full">
                    <div class="bg-gradient-to-br from-red-500 to-pink-600 rounded-xl shadow-lg p-4 md:p-6 text-white h-full flex flex-col justify-between transform transition-all duration-300 hover:scale-105">
                        <div class="flex items-center justify-between mb-3 md:mb-4">
                            <div class="bg-white/20 rounded-lg p-2 md:p-3 backdrop-blur-sm">
                                <i class="fas fa-envelope text-xl md:text-2xl"></i>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl md:text-3xl font-bold"><?= number_format($stats['unread_messages']) ?></div>
                                <div class="text-red-100 text-xs md:text-sm">Unread</div>
                            </div>
                        </div>
                        <div class="text-red-100 text-xs md:text-sm font-medium mb-1">Messages</div>
                        <div class="text-red-200 text-xs"><?= $stats['messages_today'] ?> today • <?= $stats['total_messages'] ?> total</div>
                        <div class="mt-3 md:mt-4 inline-flex items-center justify-center bg-white/20 hover:bg-white/30 px-3 md:px-4 py-2 rounded-lg transition-all text-xs md:text-sm">
                            View All <i class="fas fa-arrow-right ml-1 text-xs"></i>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($hasOrders && $stats['orders_revenue'] > 0): ?>
        <div class="dashboard-widget" data-widget-id="revenue-stat" data-default-size="2x1">
            <div class="widget-header">
                <div class="widget-title">
                    <i class="fas fa-dollar-sign mr-2"></i>
                    <span>Total Revenue</span>
                </div>
                <div class="widget-controls">
                    <button onclick="removeWidget('revenue-stat')" class="widget-btn-remove" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="widget-content">
                <div class="bg-gradient-to-r from-green-500 via-emerald-500 to-green-600 rounded-xl shadow-xl p-4 md:p-6 text-white h-full flex flex-col sm:flex-row items-center justify-between transform transition-all duration-300 hover:scale-105">
                    <div>
                        <p class="text-green-100 text-xs md:text-sm mb-2 font-medium">Total Revenue</p>
                        <p class="text-2xl md:text-4xl font-bold mb-2">$<?= number_format($stats['orders_revenue'], 2) ?></p>
                        <p class="text-green-100 text-xs md:text-sm">From paid orders</p>
                    </div>
                    <div class="bg-white/20 rounded-full p-4 md:p-6 backdrop-blur-sm">
                        <i class="fas fa-dollar-sign text-3xl md:text-5xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($hasOrders && !empty($recentOrders)): ?>
        <div class="dashboard-widget" data-widget-id="recent-orders" data-default-size="2x2">
            <div class="widget-header">
                <div class="widget-title">
                    <i class="fas fa-shopping-cart mr-2"></i>
                    <span>Recent Orders</span>
                </div>
                <div class="widget-controls">
                    <button onclick="removeWidget('recent-orders')" class="widget-btn-remove" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="widget-content">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden h-full flex flex-col">
                    <div class="bg-gradient-to-r from-purple-500 to-indigo-600 p-6 text-white">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold">
                                <i class="fas fa-shopping-cart mr-2"></i>
                                Recent Orders
                            </h2>
                            <a href="<?= url('admin/orders.php') ?>" class="text-purple-100 hover:text-white text-sm font-medium transition-colors">
                                View All <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="p-6 flex-1 overflow-y-auto">
                        <div class="space-y-4">
                            <?php foreach ($recentOrders as $order): ?>
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors border-l-4 border-purple-500">
                                <div class="flex-1">
                                    <a href="<?= url('admin/order-view.php?id=' . $order['id']) ?>" 
                                       class="text-blue-600 hover:text-blue-800 font-semibold text-sm">
                                        <?= escape($order['order_number']) ?>
                                    </a>
                                    <p class="text-xs text-gray-600 mt-1">
                                        <?php if ($order['first_name'] || $order['last_name']): ?>
                                            <i class="fas fa-user text-gray-400 mr-1"></i>
                                            <?= escape($order['first_name'] . ' ' . $order['last_name']) ?>
                                        <?php else: ?>
                                            <i class="fas fa-user text-gray-400 mr-1"></i>
                                            Guest
                                        <?php endif; ?>
                                        • <i class="fas fa-box text-gray-400 mr-1"></i>
                                        <?= escape($order['item_count'] ?? 0) ?> item(s)
                                    </p>
                                </div>
                                <div class="text-right ml-4">
                                    <span class="font-bold text-green-600 text-lg">$<?= number_format($order['total'], 2) ?></span>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-clock mr-1"></i>
                                        <?= date('M d', strtotime($order['created_at'])) ?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="dashboard-widget" data-widget-id="recent-products" data-default-size="2x2">
            <div class="widget-header">
                <div class="widget-title">
                    <i class="fas fa-box mr-2"></i>
                    <span>Recent Products</span>
                </div>
                <div class="widget-controls">
                    <button onclick="removeWidget('recent-products')" class="widget-btn-remove" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="widget-content">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden h-full flex flex-col">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-600 p-6 text-white">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold">
                                <i class="fas fa-box mr-2"></i>
                                Recent Products
                            </h2>
                            <a href="<?= url('admin/products.php') ?>" class="text-blue-100 hover:text-white text-sm font-medium transition-colors">
                                View All <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="p-6 flex-1 overflow-y-auto">
                        <div class="space-y-4">
                            <?php foreach ($recentProducts as $product): ?>
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors border-l-4 border-blue-500">
                                <div class="flex-1">
                                    <a href="<?= url('product.php?slug=' . escape($product['slug'])) ?>" target="_blank" 
                                       class="text-blue-600 hover:text-blue-800 font-semibold text-sm">
                                        <?= escape($product['name']) ?>
                                    </a>
                                    <p class="text-xs text-gray-600 mt-1">
                                        <i class="fas fa-tag text-gray-400 mr-1"></i>
                                        SKU: <?= escape($product['sku'] ?? 'N/A') ?>
                                    </p>
                                </div>
                                <div class="text-right ml-4">
                                    <span class="text-xs text-gray-500">
                                        <i class="fas fa-calendar mr-1"></i>
                                        <?= date('M d, Y', strtotime($product['created_at'])) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-widget" data-widget-id="recent-quotes" data-default-size="2x2">
            <div class="widget-header">
                <div class="widget-title">
                    <i class="fas fa-calculator mr-2"></i>
                    <span>Recent Quote Requests</span>
                </div>
                <div class="widget-controls">
                    <button onclick="removeWidget('recent-quotes')" class="widget-btn-remove" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="widget-content">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden h-full flex flex-col">
                    <div class="bg-gradient-to-r from-yellow-500 to-amber-600 p-6 text-white">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold">
                                <i class="fas fa-calculator mr-2"></i>
                                Recent Quote Requests
                            </h2>
                            <a href="<?= url('admin/quotes.php') ?>" class="text-yellow-100 hover:text-white text-sm font-medium transition-colors">
                                View All <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="p-6 flex-1 overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($recentQuotes as $quote): ?>
                            <div class="p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors border-l-4 <?= $quote['status'] === 'pending' ? 'border-yellow-500' : 'border-green-500' ?>">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-800"><?= escape($quote['name']) ?></p>
                                        <?php if ($quote['product_name']): ?>
                                            <p class="text-sm text-gray-600 mt-1">
                                                <i class="fas fa-box text-gray-400 mr-1"></i>
                                                <?= escape($quote['product_name']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $quote['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                                        <?= ucfirst($quote['status']) ?>
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?= date('M d, Y', strtotime($quote['created_at'])) ?>
                                </p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Required Libraries -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>

<script>
let editMode = false;
let dashboardLayout = null;
let sortableInstance = null;
let interactInstances = [];

// Load saved layout from localStorage
function loadDashboardLayout() {
    const saved = localStorage.getItem('dashboard_layout');
    if (saved) {
        try {
            dashboardLayout = JSON.parse(saved);
            applyLayout();
        } catch (e) {
            console.error('Error loading layout:', e);
            dashboardLayout = {};
        }
    } else {
        dashboardLayout = {};
    }
}

// Save layout to localStorage
function saveDashboardLayout() {
    const layout = {};
    document.querySelectorAll('.dashboard-widget').forEach(widget => {
        const id = widget.dataset.widgetId;
        const computedStyle = window.getComputedStyle(widget);
        const gridColumn = computedStyle.gridColumnStart !== 'auto' ? 
            `${computedStyle.gridColumnStart} / ${computedStyle.gridColumnEnd}` : '';
        const gridRow = computedStyle.gridRowStart !== 'auto' ? 
            `${computedStyle.gridRowStart} / ${computedStyle.gridRowEnd}` : '';
        const width = widget.style.width || '';
        const height = widget.style.height || '';
        const transform = widget.style.transform || '';
        
        layout[id] = {
            gridColumn,
            gridRow,
            width,
            height,
            transform,
            visible: !widget.classList.contains('hidden'),
            order: Array.from(widget.parentNode.children).indexOf(widget)
        };
    });
    localStorage.setItem('dashboard_layout', JSON.stringify(layout));
    dashboardLayout = layout;
}

// Apply saved layout
function applyLayout() {
    if (!dashboardLayout || Object.keys(dashboardLayout).length === 0) return;
    
    document.querySelectorAll('.dashboard-widget').forEach(widget => {
        const id = widget.dataset.widgetId;
        const saved = dashboardLayout[id];
        if (saved) {
            if (saved.gridColumn) widget.style.gridColumn = saved.gridColumn;
            if (saved.gridRow) widget.style.gridRow = saved.gridRow;
            if (saved.width) widget.style.width = saved.width;
            if (saved.height) widget.style.height = saved.height;
            if (saved.transform) widget.style.transform = saved.transform;
            if (saved.visible === false) widget.classList.add('hidden');
            if (saved.order !== undefined) widget.style.order = saved.order;
        }
    });
}

// Toggle edit mode
function toggleEditMode() {
    editMode = !editMode;
    const btn = document.getElementById('editModeBtn');
    const widgets = document.querySelectorAll('.dashboard-widget');
    const grid = document.getElementById('dashboardGrid');
    
    if (editMode) {
        btn.innerHTML = '<i class="fas fa-save"></i> <span class="hidden sm:inline">Save Layout</span>';
        btn.classList.remove('bg-white/20');
        btn.classList.add('bg-green-500', 'hover:bg-green-600');
        grid.classList.add('edit-mode-grid');
        
        widgets.forEach(widget => {
            if (!widget.classList.contains('hidden')) {
                widget.classList.add('edit-mode');
            }
        });
        
        initDragAndDrop();
        initResize();
    } else {
        btn.innerHTML = '<i class="fas fa-edit"></i> <span class="hidden sm:inline">Edit Layout</span>';
        btn.classList.remove('bg-green-500', 'hover:bg-green-600');
        btn.classList.add('bg-white/20');
        grid.classList.remove('edit-mode-grid');
        
        widgets.forEach(widget => {
            widget.classList.remove('edit-mode');
        });
        
        // Cleanup
        if (sortableInstance) {
            sortableInstance.destroy();
            sortableInstance = null;
        }
        interactInstances.forEach(instance => {
            if (instance && instance.unset) instance.unset();
        });
        interactInstances = [];
        
        saveDashboardLayout();
    }
}

// Initialize drag and drop
function initDragAndDrop() {
    const grid = document.getElementById('dashboardGrid');
    
    if (sortableInstance) {
        sortableInstance.destroy();
    }
    
    sortableInstance = new Sortable(grid, {
        animation: 200,
        handle: '.widget-header',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        forceFallback: true,
        fallbackOnBody: true,
        swapThreshold: 0.65,
        onEnd: function(evt) {
            saveDashboardLayout();
        }
    });
}

// Initialize resize
function initResize() {
    document.querySelectorAll('.dashboard-widget.edit-mode').forEach(widget => {
        const instance = interact(widget).resizable({
            edges: { 
                left: '.resize-handle-left',
                right: '.resize-handle-right', 
                top: '.resize-handle-top', 
                bottom: '.resize-handle-bottom'
            },
            listeners: {
                start(event) {
                    event.target.classList.add('resizing');
                },
                move(event) {
                    const target = event.target;
                    let x = (parseFloat(target.getAttribute('data-x')) || 0);
                    let y = (parseFloat(target.getAttribute('data-y')) || 0);
                    
                    x += event.deltaRect.left;
                    y += event.deltaRect.top;
                    
                    Object.assign(target.style, {
                        width: `${event.rect.width}px`,
                        height: `${event.rect.height}px`,
                        transform: `translate(${x}px, ${y}px)`
                    });
                    
                    target.setAttribute('data-x', x);
                    target.setAttribute('data-y', y);
                },
                end(event) {
                    event.target.classList.remove('resizing');
                    saveDashboardLayout();
                }
            },
            modifiers: [
                interact.modifiers.restrictSize({
                    min: { width: 250, height: 200 }
                }),
                interact.modifiers.restrictEdges({
                    outer: 'parent'
                })
            ],
            inertia: true
        }).draggable({
            handle: '.widget-header',
            listeners: {
                start(event) {
                    event.target.classList.add('dragging');
                },
                move(event) {
                    const target = event.target;
                    const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                    const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;
                    
                    target.style.transform = `translate(${x}px, ${y}px)`;
                    target.setAttribute('data-x', x);
                    target.setAttribute('data-y', y);
                },
                end(event) {
                    event.target.classList.remove('dragging');
                    saveDashboardLayout();
                }
            },
            inertia: true,
            modifiers: [
                interact.modifiers.restrict({
                    restriction: 'parent',
                    endOnly: true
                })
            ]
        });
        
        interactInstances.push(instance);
    });
}

// Remove widget
function removeWidget(widgetId) {
    if (confirm('Remove this widget from dashboard?')) {
        const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
        if (widget) {
            widget.classList.add('hidden');
            saveDashboardLayout();
        }
    }
}

// Reset dashboard
function resetDashboard() {
    if (confirm('Reset dashboard to default layout? This will clear all customizations.')) {
        localStorage.removeItem('dashboard_layout');
        location.reload();
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardLayout();
});
</script>

<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    padding: 0;
    position: relative;
}

.dashboard-grid.edit-mode-grid {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
}

.dashboard-widget {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: all 0.3s ease;
    min-height: 200px;
    position: relative;
    display: flex;
    flex-direction: column;
}

.dashboard-widget.edit-mode {
    border: 2px dashed #3b82f6;
    cursor: move;
    z-index: 10;
}

.dashboard-widget.edit-mode.dragging {
    opacity: 0.8;
    z-index: 1000;
    transform: rotate(2deg);
}

.dashboard-widget.edit-mode.resizing {
    border-color: #10b981;
}

.dashboard-widget.edit-mode .widget-header {
    background: #eff6ff;
    cursor: grab;
    user-select: none;
}

.dashboard-widget.edit-mode .widget-header:active {
    cursor: grabbing;
}

.widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    flex-shrink: 0;
}

.widget-title {
    display: flex;
    align-items: center;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.widget-controls {
    display: none;
}

.edit-mode .widget-controls {
    display: flex;
    gap: 0.5rem;
}

.widget-btn-remove {
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 0.375rem;
    padding: 0.25rem 0.5rem;
    cursor: pointer;
    font-size: 0.75rem;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.widget-btn-remove:hover {
    background: #dc2626;
}

.widget-content {
    padding: 0;
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* Resize handles */
.resize-handle {
    position: absolute;
    background: #3b82f6;
    z-index: 20;
    opacity: 0;
    transition: opacity 0.2s;
}

.dashboard-widget.edit-mode:hover .resize-handle {
    opacity: 1;
}

.resize-handle-top {
    top: 0;
    left: 0;
    right: 0;
    height: 8px;
    cursor: ns-resize;
}

.resize-handle-bottom {
    bottom: 0;
    left: 0;
    right: 0;
    height: 8px;
    cursor: ns-resize;
}

.resize-handle-left {
    left: 0;
    top: 0;
    bottom: 0;
    width: 8px;
    cursor: ew-resize;
}

.resize-handle-right {
    right: 0;
    top: 0;
    bottom: 0;
    width: 8px;
    cursor: ew-resize;
}

.resize-handle-corner {
    width: 16px;
    height: 16px;
    background: #10b981;
    border-radius: 50%;
    z-index: 21;
}

.resize-handle-top-left {
    top: -8px;
    left: -8px;
    cursor: nwse-resize;
}

.resize-handle-top-right {
    top: -8px;
    right: -8px;
    cursor: nesw-resize;
}

.resize-handle-bottom-left {
    bottom: -8px;
    left: -8px;
    cursor: nesw-resize;
}

.resize-handle-bottom-right {
    bottom: -8px;
    right: -8px;
    cursor: nwse-resize;
}

.sortable-ghost {
    opacity: 0.4;
    background: #cbd5e1;
    border: 2px dashed #3b82f6;
}

.sortable-chosen {
    cursor: grabbing !important;
}

.sortable-drag {
    opacity: 0.8;
    transform: rotate(2deg);
}

/* Enhanced Widget Interactions */
.dashboard-widget {
    cursor: pointer;
    transform: translateY(0);
}

.dashboard-widget:not(.edit-mode):hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
}

.dashboard-widget:not(.edit-mode):active {
    transform: translateY(-2px);
}

.widget-content a {
    transition: all 0.3s ease;
}

.widget-content a:hover {
    transform: translateX(4px);
}

/* Pulse animation for stats */
@keyframes pulse-glow {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
    }
    50% {
        box-shadow: 0 0 0 8px rgba(59, 130, 246, 0);
    }
}

.dashboard-widget[data-widget-id*="stat"]:hover .widget-content > div {
    animation: pulse-glow 2s infinite;
}

/* Loading skeleton */
.widget-loading {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

/* Touch-friendly controls */
@media (hover: none) and (pointer: coarse) {
    .dashboard-widget {
        min-height: 250px;
    }
    
    .widget-btn-remove {
        padding: 0.5rem;
        font-size: 1rem;
    }
    
    .resize-handle {
        width: 12px;
        height: 12px;
    }
    
    .resize-handle-corner {
        width: 24px;
        height: 24px;
    }
}

/* Responsive Grid Improvements */
@media (max-width: 640px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .dashboard-widget {
        min-height: 180px;
    }
    
    .widget-content {
        padding: 0.75rem;
    }
    
    .widget-content .text-3xl {
        font-size: 1.75rem;
    }
    
    .widget-content .text-4xl {
        font-size: 2rem;
    }
}

@media (min-width: 641px) and (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }
}

@media (min-width: 1025px) {
    .dashboard-grid {
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    }
}

/* Enhanced Edit Mode */
.dashboard-widget.edit-mode {
    border: 2px dashed #3b82f6;
    cursor: move;
    z-index: 10;
    transition: all 0.2s ease;
}

.dashboard-widget.edit-mode:hover {
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.dashboard-widget.edit-mode.dragging {
    opacity: 0.8;
    z-index: 1000;
    transform: rotate(2deg) scale(1.02);
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.3);
}

.dashboard-widget.edit-mode.resizing {
    border-color: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
}

/* Smooth transitions */
.dashboard-widget,
.widget-content,
.widget-header {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Improved widget cards */
.widget-content > div {
    transition: all 0.3s ease;
}

.widget-content > div:hover {
    transform: scale(1.02);
}

/* Better mobile touch targets */
@media (max-width: 768px) {
    .dashboard-widget.edit-mode {
        border-width: 2px;
    }
    
    .widget-header {
        padding: 1rem;
    }
    
    .widget-title {
        font-size: 1rem;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
