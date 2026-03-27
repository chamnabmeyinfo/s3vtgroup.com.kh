<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Product;

$productId = $_GET['id'] ?? null;
$productModel = new Product();

if (!$productId) {
    header('Location: ' . url('admin/products.php'));
    exit;
}

$product = $productModel->getById($productId);

if (!$product) {
    header('Location: ' . url('admin/products.php'));
    exit;
}

try {
    $db = db();
    $db->getPdo()->beginTransaction();
    
    // Create duplicate product
    $newData = [
        'name' => $product['name'] . ' (Copy)',
        'slug' => $product['slug'] . '-copy-' . time(),
        'sku' => $product['sku'] ? $product['sku'] . '-COPY-' . time() : null,
        'description' => $product['description'],
        'short_description' => $product['short_description'],
        'price' => $product['price'],
        'sale_price' => $product['sale_price'],
        'category_id' => $product['category_id'],
        'image' => $product['image'],
        'gallery' => $product['gallery'],
        'specifications' => $product['specifications'],
        'features' => $product['features'],
        'stock_status' => $product['stock_status'],
        'weight' => $product['weight'],
        'dimensions' => $product['dimensions'],
        'is_featured' => 0, // Don't duplicate featured status
        'is_active' => 0, // Make inactive by default
        'meta_title' => $product['meta_title'],
        'meta_description' => $product['meta_description'],
    ];
    
    $newId = $productModel->create($newData);
    
    // Duplicate product variants if they exist
    try {
        $variants = $db->fetchAll(
            "SELECT * FROM product_variants WHERE product_id = :product_id",
            ['product_id' => $productId]
        );
        
        foreach ($variants as $variant) {
            $variantData = [
                'product_id' => $newId,
                'name' => $variant['name'],
                'sku' => $variant['sku'] ? $variant['sku'] . '-COPY-' . time() : null,
                'price' => $variant['price'],
                'sale_price' => $variant['sale_price'],
                'stock_quantity' => $variant['stock_quantity'],
                'stock_status' => $variant['stock_status'],
                'image' => $variant['image'],
                'is_active' => $variant['is_active'],
                'sort_order' => $variant['sort_order'],
            ];
            
            $newVariantId = $db->insert('product_variants', $variantData);
            
            // Duplicate variant images if they exist
            try {
                $variantImages = $db->fetchAll(
                    "SELECT * FROM product_variant_images WHERE variant_id = :variant_id",
                    ['variant_id' => $variant['id']]
                );
                
                foreach ($variantImages as $image) {
                    $db->insert('product_variant_images', [
                        'variant_id' => $newVariantId,
                        'image' => $image['image'],
                        'sort_order' => $image['sort_order'],
                    ]);
                }
            } catch (\Exception $e) {
                // Variant images table might not exist - continue
            }
        }
    } catch (\Exception $e) {
        // Variants table might not exist - continue without duplicating variants
    }
    
    $db->getPdo()->commit();
    
    header('Location: ' . url('admin/product-edit.php?id=' . $newId . '&message=Product duplicated successfully with all variants'));
    exit;
    
} catch (\Exception $e) {
    if ($db->getPdo()->inTransaction()) {
        $db->getPdo()->rollBack();
    }
    header('Location: ' . url('admin/products.php?error=Error duplicating product: ' . urlencode($e->getMessage())));
    exit;
}

