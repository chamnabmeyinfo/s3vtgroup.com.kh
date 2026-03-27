<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

// Check if orders table exists FIRST - before importing Order model
$ordersTableExists = false;
try {
    db()->fetchOne("SELECT 1 FROM orders LIMIT 1");
    $ordersTableExists = true;
} catch (Exception $e) {
    $ordersTableExists = false;
}

// Don't create Order model if table doesn't exist
if (!$ordersTableExists) {
    $pageTitle = 'Orders';
    include __DIR__ . '/includes/header.php';
    ?>
    <div class="p-6">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-yellow-900 mb-2">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Orders Management Not Set Up
            </h2>
            <p class="text-yellow-800 mb-4">
                The orders tables haven't been created yet. Please run the setup script to create them.
            </p>
            <a href="<?= url('admin/setup-orders.php') ?>" class="btn-primary inline-block">
                <i class="fas fa-database mr-2"></i>
                Setup Orders Management
            </a>
        </div>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Table exists - safe to import and use Order model now
use App\Models\Order;
$orderModel = new Order();
$message = '';
$error = '';

// Handle status update
if (!empty($_GET['update_status']) && !empty($_GET['id'])) {
    $status = $_GET['update_status'];
    $id = (int)$_GET['id'];
    
    if (in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
        $orderModel->update($id, ['status' => $status]);
        $message = 'Order status updated successfully.';
    }
}

// Handle payment status update
if (!empty($_GET['update_payment_status']) && !empty($_GET['id'])) {
    $paymentStatus = $_GET['update_payment_status'];
    $id = (int)$_GET['id'];
    
    if (in_array($paymentStatus, ['pending', 'paid', 'failed', 'refunded'])) {
        $orderModel->update($id, ['payment_status' => $paymentStatus]);
        $message = 'Payment status updated successfully.';
    }
}

// Handle delete
if (!empty($_GET['delete'])) {
    try {
        $orderId = (int)$_GET['delete'];
        
        // Validate ID
        if ($orderId <= 0) {
            $error = 'Invalid order ID.';
        } else {
            // Check if order exists
            $order = $orderModel->getById($orderId);
            if (!$order) {
                $error = 'Order not found.';
            } else {
                // Perform delete
                $orderModel->delete($orderId);
                $message = 'Order deleted successfully.';
            }
        }
    } catch (\Exception $e) {
        $error = 'Error deleting order: ' . $e->getMessage();
    }
}

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$paymentStatusFilter = $_GET['payment_status'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$sort = $_GET['sort'] ?? 'date_desc';

// Build filters array for Order model
$orderFilters = [];
if ($statusFilter !== 'all') {
    $orderFilters['status'] = $statusFilter;
}
if ($paymentStatusFilter !== 'all') {
    $orderFilters['payment_status'] = $paymentStatusFilter;
}
if ($search) {
    $orderFilters['search'] = $search;
}
if ($dateFrom) {
    $orderFilters['date_from'] = $dateFrom;
}
if ($dateTo) {
    $orderFilters['date_to'] = $dateTo;
}
$orderFilters['sort'] = $sort;

// Get orders
$orders = $orderModel->getAll($orderFilters);

// Calculate stats for mini dashboard
$allOrders = $orderModel->getAll([]);
$totalOrders = count($allOrders);
$pendingOrders = count(array_filter($allOrders, fn($o) => $o['status'] === 'pending'));
$processingOrders = count(array_filter($allOrders, fn($o) => $o['status'] === 'processing'));
$deliveredOrders = count(array_filter($allOrders, fn($o) => $o['status'] === 'delivered'));
$totalRevenue = array_sum(array_map(fn($o) => $o['total'] ?? 0, array_filter($allOrders, fn($o) => $o['payment_status'] === 'paid')));

$miniStats = [
    [
        'label' => 'Total Orders',
        'value' => number_format($totalOrders),
        'icon' => 'fas fa-shopping-cart',
        'color' => 'from-purple-500 to-indigo-600',
        'description' => 'All orders',
        'link' => url('admin/orders.php')
    ],
    [
        'label' => 'Pending',
        'value' => number_format($pendingOrders),
        'icon' => 'fas fa-clock',
        'color' => 'from-yellow-500 to-amber-600',
        'description' => 'Awaiting processing',
        'link' => url('admin/orders.php?status=pending')
    ],
    [
        'label' => 'Processing',
        'value' => number_format($processingOrders),
        'icon' => 'fas fa-cog',
        'color' => 'from-blue-500 to-cyan-600',
        'description' => 'In progress',
        'link' => url('admin/orders.php?status=processing')
    ],
    [
        'label' => 'Total Revenue',
        'value' => '$' . number_format($totalRevenue, 2),
        'icon' => 'fas fa-dollar-sign',
        'color' => 'from-green-500 to-emerald-600',
        'description' => 'From paid orders',
        'link' => url('admin/orders.php?payment_status=paid')
    ]
];

// Column visibility
$selectedColumns = $_GET['columns'] ?? ['order_number', 'date', 'customer', 'items', 'total', 'status', 'payment_status', 'actions'];
$availableColumns = [
    'order_number' => 'Order Number',
    'date' => 'Date',
    'customer' => 'Customer',
    'email' => 'Email',
    'phone' => 'Phone',
    'items' => 'Items',
    'subtotal' => 'Subtotal',
    'tax' => 'Tax',
    'shipping' => 'Shipping',
    'total' => 'Total',
    'status' => 'Status',
    'payment_status' => 'Payment Status',
    'actions' => 'Actions'
];

// Setup filter component
$filterId = 'orders-filter';
$defaultColumns = $selectedColumns;
$filters = [
    'search' => true,
    'date_range' => true,
    'status' => [
        'options' => [
            'all' => 'All Statuses',
            'pending' => 'Pending',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled'
        ]
    ]
];

$sortOptions = [
    'date_desc' => 'Date (Newest)',
    'date_asc' => 'Date (Oldest)',
    'total_desc' => 'Total (High to Low)',
    'total_asc' => 'Total (Low to High)',
    'number_desc' => 'Order Number (Desc)',
    'number_asc' => 'Order Number (Asc)'
];

$pageTitle = 'Orders';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl shadow-xl p-4 md:p-6 lg:p-8 mb-4 md:mb-6 text-white">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-1 md:mb-2">
                    <i class="fas fa-shopping-cart mr-2 md:mr-3"></i>
                    Orders Management
                </h1>
                <p class="text-purple-100 text-sm md:text-lg">Manage customer orders and transactions</p>
            </div>
            <a href="<?= url('admin/orders-export.php') ?>" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all w-full sm:w-auto text-center text-sm md:text-base">
                <i class="fas fa-download mr-2"></i>
                Export
            </a>
        </div>
    </div>

    <!-- Mini Dashboard Stats -->
    <?php 
    $stats = $miniStats;
    include __DIR__ . '/includes/mini-stats.php'; 
    ?>

    <?php if (!empty($message)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2 text-xl"></i>
            <span class="font-semibold"><?= escape($message) ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2 text-xl"></i>
            <span class="font-semibold"><?= escape($error) ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Advanced Filters -->
    <?php include __DIR__ . '/includes/advanced-filters.php'; ?>
    
    <!-- Stats Bar -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-6">
                <div>
                    <span class="text-sm text-gray-600">Total Orders:</span>
                    <span class="ml-2 font-bold text-gray-900"><?= count($orders) ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto -mx-4 md:mx-0">
            <div class="inline-block min-w-full align-middle">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <?php if (in_array('order_number', $selectedColumns)): ?>
                        <th data-column="order_number" class="px-4 py-3 text-left">Order Number</th>
                        <?php endif; ?>
                        <?php if (in_array('date', $selectedColumns)): ?>
                        <th data-column="date" class="px-4 py-3 text-left">Date</th>
                        <?php endif; ?>
                        <?php if (in_array('customer', $selectedColumns)): ?>
                        <th data-column="customer" class="px-4 py-3 text-left">Customer</th>
                        <?php endif; ?>
                        <?php if (in_array('email', $selectedColumns)): ?>
                        <th data-column="email" class="px-4 py-3 text-left">Email</th>
                        <?php endif; ?>
                        <?php if (in_array('phone', $selectedColumns)): ?>
                        <th data-column="phone" class="px-4 py-3 text-left">Phone</th>
                        <?php endif; ?>
                        <?php if (in_array('items', $selectedColumns)): ?>
                        <th data-column="items" class="px-4 py-3 text-center">Items</th>
                        <?php endif; ?>
                        <?php if (in_array('subtotal', $selectedColumns)): ?>
                        <th data-column="subtotal" class="px-4 py-3 text-right">Subtotal</th>
                        <?php endif; ?>
                        <?php if (in_array('tax', $selectedColumns)): ?>
                        <th data-column="tax" class="px-4 py-3 text-right">Tax</th>
                        <?php endif; ?>
                        <?php if (in_array('shipping', $selectedColumns)): ?>
                        <th data-column="shipping" class="px-4 py-3 text-right">Shipping</th>
                        <?php endif; ?>
                        <?php if (in_array('total', $selectedColumns)): ?>
                        <th data-column="total" class="px-4 py-3 text-right">Total</th>
                        <?php endif; ?>
                        <?php if (in_array('status', $selectedColumns)): ?>
                        <th data-column="status" class="px-4 py-3 text-center">Status</th>
                        <?php endif; ?>
                        <?php if (in_array('payment_status', $selectedColumns)): ?>
                        <th data-column="payment_status" class="px-4 py-3 text-center">Payment</th>
                        <?php endif; ?>
                        <?php if (in_array('actions', $selectedColumns)): ?>
                        <th data-column="actions" class="px-4 py-3 text-center">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="100%" class="px-4 py-8 text-center text-gray-500">
                            No orders found.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <?php if (in_array('order_number', $selectedColumns)): ?>
                        <td data-column="order_number" class="px-4 py-3">
                            <a href="<?= url('admin/order-view.php?id=' . $order['id']) ?>" class="text-blue-600 hover:underline font-semibold">
                                <?= escape($order['order_number']) ?>
                            </a>
                        </td>
                        <?php endif; ?>
                        <?php if (in_array('date', $selectedColumns)): ?>
                        <td data-column="date" class="px-4 py-3 text-sm text-gray-600">
                            <?= date('M d, Y', strtotime($order['created_at'])) ?><br>
                            <span class="text-xs text-gray-400"><?= date('H:i', strtotime($order['created_at'])) ?></span>
                        </td>
                        <?php endif; ?>
                        <?php if (in_array('customer', $selectedColumns)): ?>
                        <td data-column="customer" class="px-4 py-3">
                            <?php if ($order['first_name'] || $order['last_name']): ?>
                                <?= escape($order['first_name'] . ' ' . $order['last_name']) ?>
                            <?php else: ?>
                                <span class="text-gray-400">Guest</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <?php if (in_array('email', $selectedColumns)): ?>
                        <td data-column="email" class="px-4 py-3 text-sm">
                            <?= escape($order['customer_email'] ?? 'N/A') ?>
                        </td>
                        <?php endif; ?>
                        <?php if (in_array('phone', $selectedColumns)): ?>
                        <td data-column="phone" class="px-4 py-3 text-sm">
                            <?= escape($order['customer_phone'] ?? 'N/A') ?>
                        </td>
                        <?php endif; ?>
                        <?php if (in_array('items', $selectedColumns)): ?>
                        <td data-column="items" class="px-4 py-3 text-center">
                            <?= escape($order['item_count'] ?? 0) ?>
                        </td>
                        <?php endif; ?>
                        <?php if (in_array('subtotal', $selectedColumns)): ?>
                        <td data-column="subtotal" class="px-4 py-3 text-right">
                            $<?= number_format($order['subtotal'], 2) ?>
                        </td>
                        <?php endif; ?>
                        <?php if (in_array('tax', $selectedColumns)): ?>
                        <td data-column="tax" class="px-4 py-3 text-right">
                            $<?= number_format($order['tax'] ?? 0, 2) ?>
                        </td>
                        <?php endif; ?>
                        <?php if (in_array('shipping', $selectedColumns)): ?>
                        <td data-column="shipping" class="px-4 py-3 text-right">
                            $<?= number_format($order['shipping'] ?? 0, 2) ?>
                        </td>
                        <?php endif; ?>
                        <?php if (in_array('total', $selectedColumns)): ?>
                        <td data-column="total" class="px-4 py-3 text-right font-bold text-blue-600">
                            $<?= number_format($order['total'], 2) ?>
                        </td>
                        <?php endif; ?>
                        <?php if (in_array('status', $selectedColumns)): ?>
                        <td data-column="status" class="px-4 py-3 text-center">
                            <?php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'processing' => 'bg-blue-100 text-blue-800',
                                'shipped' => 'bg-purple-100 text-purple-800',
                                'delivered' => 'bg-green-100 text-green-800',
                                'cancelled' => 'bg-red-100 text-red-800'
                            ];
                            $statusColor = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="px-2 py-1 rounded text-xs font-semibold <?= $statusColor ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </td>
                        <?php endif; ?>
                        <?php if (in_array('payment_status', $selectedColumns)): ?>
                        <td data-column="payment_status" class="px-4 py-3 text-center">
                            <?php
                            $paymentColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'paid' => 'bg-green-100 text-green-800',
                                'failed' => 'bg-red-100 text-red-800',
                                'refunded' => 'bg-gray-100 text-gray-800'
                            ];
                            $paymentColor = $paymentColors[$order['payment_status']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="px-2 py-1 rounded text-xs font-semibold <?= $paymentColor ?>">
                                <?= ucfirst($order['payment_status']) ?>
                            </span>
                        </td>
                        <?php endif; ?>
                        <?php if (in_array('actions', $selectedColumns)): ?>
                        <td data-column="actions" class="px-4 py-3">
                            <div class="flex gap-2 justify-center">
                                <a href="<?= url('admin/order-view.php?id=' . $order['id']) ?>" 
                                   class="text-blue-600 hover:text-blue-800" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <div class="relative inline-block">
                                    <button onclick="toggleDropdown(<?= $order['id'] ?>)" 
                                            class="text-gray-600 hover:text-gray-800" title="Actions">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div id="dropdown-<?= $order['id'] ?>" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border">
                                        <div class="py-1">
                                            <a href="<?= url('admin/order-view.php?id=' . $order['id']) ?>" 
                                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <i class="fas fa-eye mr-2"></i> View Details
                                            </a>
                                            <div class="border-t">
                                                <div class="px-4 py-2 text-xs font-semibold text-gray-500">Update Status</div>
                                                <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status): ?>
                                                    <?php if ($order['status'] !== $status): ?>
                                                    <a href="?update_status=<?= $status ?>&id=<?= $order['id'] ?>" 
                                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                       onclick="return confirm('Update order status to <?= ucfirst($status) ?>?')">
                                                        → <?= ucfirst($status) ?>
                                                    </a>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="border-t">
                                                <div class="px-4 py-2 text-xs font-semibold text-gray-500">Payment Status</div>
                                                <?php foreach (['pending', 'paid', 'failed', 'refunded'] as $pStatus): ?>
                                                    <?php if ($order['payment_status'] !== $pStatus): ?>
                                                    <a href="?update_payment_status=<?= $pStatus ?>&id=<?= $order['id'] ?>" 
                                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                       onclick="return confirm('Update payment status to <?= ucfirst($pStatus) ?>?')">
                                                        → <?= ucfirst($pStatus) ?>
                                                    </a>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="border-t">
                                                <a href="?delete=<?= $order['id'] ?>" 
                                                   class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                                                   onclick="return confirm('Are you sure you want to delete this order? This action cannot be undone.')">
                                                    <i class="fas fa-trash mr-2"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDropdown(orderId) {
    // Close all other dropdowns
    document.querySelectorAll('[id^="dropdown-"]').forEach(dropdown => {
        if (dropdown.id !== 'dropdown-' + orderId) {
            dropdown.classList.add('hidden');
        }
    });
    
    // Toggle current dropdown
    const dropdown = document.getElementById('dropdown-' + orderId);
    dropdown.classList.toggle('hidden');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('[onclick*="toggleDropdown"]') && !event.target.closest('[id^="dropdown-"]')) {
        document.querySelectorAll('[id^="dropdown-"]').forEach(dropdown => {
            dropdown.classList.add('hidden');
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

