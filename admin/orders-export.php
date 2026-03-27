<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Order;

$orderModel = new Order();

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$paymentStatusFilter = $_GET['payment_status'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build filters
$filters = [];
if ($statusFilter !== 'all') {
    $filters['status'] = $statusFilter;
}
if ($paymentStatusFilter !== 'all') {
    $filters['payment_status'] = $paymentStatusFilter;
}
if ($search) {
    $filters['search'] = $search;
}
if ($dateFrom) {
    $filters['date_from'] = $dateFrom;
}
if ($dateTo) {
    $filters['date_to'] = $dateTo;
}

// Get all orders (no limit)
$orders = $orderModel->getAll($filters);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d_His') . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Add CSV header
fputcsv($output, [
    'Order Number',
    'Date',
    'Customer Name',
    'Email',
    'Phone',
    'Items',
    'Subtotal',
    'Tax',
    'Shipping',
    'Total',
    'Status',
    'Payment Status'
]);

// Add order data
foreach ($orders as $order) {
    $customerName = '';
    if ($order['first_name'] || $order['last_name']) {
        $customerName = trim($order['first_name'] . ' ' . $order['last_name']);
    } else {
        $customerName = 'Guest';
    }
    
    fputcsv($output, [
        $order['order_number'],
        date('Y-m-d H:i:s', strtotime($order['created_at'])),
        $customerName,
        $order['customer_email'] ?? '',
        $order['customer_phone'] ?? '',
        $order['item_count'] ?? 0,
        number_format($order['subtotal'], 2),
        number_format($order['tax'] ?? 0, 2),
        number_format($order['shipping'] ?? 0, 2),
        number_format($order['total'], 2),
        ucfirst($order['status']),
        ucfirst($order['payment_status'])
    ]);
}

fclose($output);
exit;

