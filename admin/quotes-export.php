<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

$quotes = db()->fetchAll(
    "SELECT q.*, p.name as product_name FROM quote_requests q 
     LEFT JOIN products p ON q.product_id = p.id 
     ORDER BY q.created_at DESC"
);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="quotes_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV headers
fputcsv($output, [
    'ID',
    'Name',
    'Email',
    'Phone',
    'Company',
    'Product',
    'Status',
    'Message',
    'Created At'
]);

// Add data rows
foreach ($quotes as $quote) {
    fputcsv($output, [
        $quote['id'],
        $quote['name'],
        $quote['email'],
        $quote['phone'] ?? '',
        $quote['company'] ?? '',
        $quote['product_name'] ?? 'General Inquiry',
        $quote['status'],
        strip_tags($quote['message'] ?? ''),
        $quote['created_at']
    ]);
}

fclose($output);
exit;

