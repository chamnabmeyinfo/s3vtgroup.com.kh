<?php
/**
 * Custom Tools Functions
 * 
 * Add your custom tool functions here.
 * Functions defined here can be called from the Optional Tools page.
 */

/**
 * Clear Cache Tool
 */
function clearCacheTool() {
    $output = [];
    $output[] = "Starting cache cleanup...\n";
    
    // Clear opcache if available
    if (function_exists('opcache_reset')) {
        opcache_reset();
        $output[] = "✓ Opcache cleared\n";
    }
    
    // Clear any custom cache directories
    $cacheDirs = [
        __DIR__ . '/../../storage/cache',
        __DIR__ . '/../../storage/temp',
    ];
    
    foreach ($cacheDirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '/*');
            $count = 0;
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $count++;
                }
            }
            $output[] = "✓ Cleared {$count} files from " . basename($dir) . "\n";
        }
    }
    
    $output[] = "\nCache cleanup completed!";
    return implode('', $output);
}

/**
 * Optimize Database Tool
 */
function optimizeDatabaseTool() {
    $db = db();
    $output = [];
    $output[] = "Starting database optimization...\n\n";
    
    try {
        // Get all tables
        $tables = $db->fetchAll("SHOW TABLES");
        $firstTable = reset($tables);
        $tableColumn = array_key_first($firstTable);
        
        $optimized = 0;
        foreach ($tables as $table) {
            $tableName = $table[$tableColumn];
            try {
                $db->getPdo()->exec("OPTIMIZE TABLE `{$tableName}`");
                $output[] = "✓ Optimized: {$tableName}\n";
                $optimized++;
            } catch (Exception $e) {
                $output[] = "✗ Error optimizing {$tableName}: " . $e->getMessage() . "\n";
            }
        }
        
        $output[] = "\n✓ Database optimization completed! ({$optimized} tables optimized)";
    } catch (Exception $e) {
        $output[] = "\n✗ Error: " . $e->getMessage();
    }
    
    return implode('', $output);
}

/**
 * Clean Old Logs Tool
 */
function cleanOldLogsTool() {
    $output = [];
    $output[] = "Cleaning old log files...\n\n";
    
    $logDir = __DIR__ . '/../../storage/logs';
    $daysOld = 30;
    $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
    $deleted = 0;
    
    if (is_dir($logDir)) {
        $files = glob($logDir . '/*.log');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $output[] = "✓ Deleted: " . basename($file) . "\n";
                $deleted++;
            }
        }
    }
    
    $output[] = "\n✓ Cleanup completed! ({$deleted} files deleted)";
    return implode('', $output);
}

/**
 * Regenerate Thumbnails Tool
 */
function regenerateThumbnailsTool() {
    $output = [];
    $output[] = "Regenerating thumbnails...\n\n";
    
    // This is a placeholder - implement based on your image processing needs
    $output[] = "Thumbnail regeneration feature needs to be implemented based on your image library.\n";
    $output[] = "You can customize this function in app/Tools/custom-tools.php\n";
    
    return implode('', $output);
}

/**
 * Update Product Slugs Tool
 */
function updateProductSlugsTool() {
    $db = db();
    $output = [];
    $output[] = "Updating product slugs...\n\n";
    
    try {
        $products = $db->fetchAll("SELECT id, name FROM products");
        $updated = 0;
        
        foreach ($products as $product) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $product['name'])));
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');
            
            if (empty($slug)) {
                $slug = 'product-' . $product['id'];
            }
            
            // Make unique
            $originalSlug = $slug;
            $counter = 1;
            while ($db->fetchOne("SELECT id FROM products WHERE slug = :slug AND id != :id", ['slug' => $slug, 'id' => $product['id']])) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            $db->update('products', ['slug' => $slug], 'id = :id', ['id' => $product['id']]);
            $output[] = "✓ Updated: {$product['name']} -> {$slug}\n";
            $updated++;
        }
        
        $output[] = "\n✓ Slug update completed! ({$updated} products updated)";
    } catch (Exception $e) {
        $output[] = "\n✗ Error: " . $e->getMessage();
    }
    
    return implode('', $output);
}

/**
 * Check Broken Images Tool
 */
function checkBrokenImagesTool() {
    $db = db();
    $output = [];
    $output[] = "Checking for broken images...\n\n";
    
    try {
        $products = $db->fetchAll("SELECT id, name, image FROM products WHERE image IS NOT NULL AND image != ''");
        $broken = [];
        
        foreach ($products as $product) {
            $imagePath = __DIR__ . '/../../storage/uploads/' . $product['image'];
            if (!file_exists($imagePath)) {
                $broken[] = $product;
                $output[] = "✗ Broken: Product #{$product['id']} - {$product['name']} ({$product['image']})\n";
            }
        }
        
        if (empty($broken)) {
            $output[] = "\n✓ No broken images found!";
        } else {
            $output[] = "\n✗ Found " . count($broken) . " products with broken images.";
        }
    } catch (Exception $e) {
        $output[] = "\n✗ Error: " . $e->getMessage();
    }
    
    return implode('', $output);
}

