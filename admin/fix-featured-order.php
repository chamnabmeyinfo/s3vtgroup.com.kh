<?php
/**
 * Quick Fix: Add featured_order column
 * Run this file directly to fix the database error
 */

require_once __DIR__ . '/../bootstrap/app.php';

// Check if column exists
try {
    $check = db()->fetchOne("
        SELECT COUNT(*) as count 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'products' 
        AND COLUMN_NAME = 'featured_order'
    ");
    
    if ($check && $check['count'] > 0) {
        echo "✓ Column 'featured_order' already exists.\n";
    } else {
        // Add column
        db()->query("ALTER TABLE products ADD COLUMN featured_order INT DEFAULT 0 AFTER is_featured");
        echo "✓ Column 'featured_order' added successfully.\n";
    }
    
    // Check if index exists
    $checkIndex = db()->fetchOne("
        SELECT COUNT(*) as count 
        FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'products' 
        AND INDEX_NAME = 'idx_featured_order'
    ");
    
    if ($checkIndex && $checkIndex['count'] > 0) {
        echo "✓ Index 'idx_featured_order' already exists.\n";
    } else {
        // Add index
        db()->query("ALTER TABLE products ADD INDEX idx_featured_order (is_featured, featured_order)");
        echo "✓ Index 'idx_featured_order' added successfully.\n";
    }
    
    // Update existing featured products
    db()->query("
        UPDATE products 
        SET featured_order = id 
        WHERE is_featured = 1 
        AND (featured_order IS NULL OR featured_order = 0)
    ");
    echo "✓ Updated existing featured products.\n";
    
    echo "\n✅ Database fix completed successfully!\n";
    echo "You can now refresh your admin page.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nYou can also run this SQL manually in phpMyAdmin:\n\n";
    echo "ALTER TABLE products ADD COLUMN featured_order INT DEFAULT 0 AFTER is_featured;\n";
    echo "ALTER TABLE products ADD INDEX idx_featured_order (is_featured, featured_order);\n";
    echo "UPDATE products SET featured_order = id WHERE is_featured = 1 AND (featured_order IS NULL OR featured_order = 0);\n";
}
