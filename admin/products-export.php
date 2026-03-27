<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Product;

$productModel = new Product();
$products = $productModel->getAll(['limit' => 1000, 'include_inactive' => true]);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="products_export_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV headers
fputcsv($output, [
    'ID',
    'Name',
    'SKU',
    'Category',
    'Price',
    'Sale Price',
    'Stock Status',
    'Featured',
    'Active',
    'Description',
    'Created At'
]);

// Add data rows
foreach ($products as $product) {
    fputcsv($output, [
        $product['id'],
        $product['name'],
        $product['sku'] ?? '',
        $product['category_name'] ?? '',
        $product['price'],
        $product['sale_price'] ?? '',
        $product['stock_status'],
        $product['is_featured'] ? 'Yes' : 'No',
        $product['is_active'] ? 'Yes' : 'No',
        strip_tags($product['description'] ?? ''),
        $product['created_at']
    ]);
}

fclose($output);
exit;

