<?php
/**
 * Run Featured Order Migration
 * This script adds the featured_order column to the products table
 */

require_once __DIR__ . '/../bootstrap/app.php';

// Check if admin is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!session('admin_logged_in')) {
    die('Unauthorized. Admin access required.');
}

$pageTitle = 'Run Featured Order Migration';
include __DIR__ . '/includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6">
                <i class="fas fa-database mr-2"></i>Featured Order Migration
            </h1>
            
            <?php
            $error = null;
            $success = false;
            $messages = [];
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
                try {
                    // Check if column already exists
                    $checkColumn = db()->fetchOne("
                        SELECT COUNT(*) as count 
                        FROM information_schema.COLUMNS 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'products' 
                        AND COLUMN_NAME = 'featured_order'
                    ");
                    
                    if ($checkColumn && $checkColumn['count'] > 0) {
                        $messages[] = "✓ Column 'featured_order' already exists.";
                    } else {
                        // Add the column
                        db()->query("ALTER TABLE products ADD COLUMN featured_order INT DEFAULT 0 AFTER is_featured");
                        $messages[] = "✓ Column 'featured_order' added successfully.";
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
                        $messages[] = "✓ Index 'idx_featured_order' already exists.";
                    } else {
                        // Add the index
                        db()->query("ALTER TABLE products ADD INDEX idx_featured_order (is_featured, featured_order)");
                        $messages[] = "✓ Index 'idx_featured_order' added successfully.";
                    }
                    
                    // Update existing featured products
                    $updated = db()->query("
                        UPDATE products 
                        SET featured_order = id 
                        WHERE is_featured = 1 
                        AND (featured_order IS NULL OR featured_order = 0)
                    ");
                    $messages[] = "✓ Updated existing featured products with default order values.";
                    
                    $success = true;
                    
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            }
            
            // Check current status
            $columnExists = false;
            $indexExists = false;
            try {
                $checkColumn = db()->fetchOne("
                    SELECT COUNT(*) as count 
                    FROM information_schema.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'products' 
                    AND COLUMN_NAME = 'featured_order'
                ");
                $columnExists = $checkColumn && $checkColumn['count'] > 0;
                
                $checkIndex = db()->fetchOne("
                    SELECT COUNT(*) as count 
                    FROM information_schema.STATISTICS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'products' 
                    AND INDEX_NAME = 'idx_featured_order'
                ");
                $indexExists = $checkIndex && $checkIndex['count'] > 0;
            } catch (Exception $e) {
                // Ignore errors during status check
            }
            ?>
            
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <strong>Error:</strong> <?= escape($error) ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700 font-semibold mb-2">Migration completed successfully!</p>
                            <ul class="list-disc list-inside text-sm text-green-700 space-y-1">
                                <?php foreach ($messages as $msg): ?>
                                    <li><?= escape($msg) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <h3 class="text-lg font-semibold text-blue-900 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>Current Status
                </h3>
                <ul class="space-y-2 text-sm text-blue-800">
                    <li>
                        <i class="fas <?= $columnExists ? 'fa-check text-green-600' : 'fa-times text-red-600' ?> mr-2"></i>
                        Column 'featured_order': <?= $columnExists ? 'Exists' : 'Missing' ?>
                    </li>
                    <li>
                        <i class="fas <?= $indexExists ? 'fa-check text-green-600' : 'fa-times text-red-600' ?> mr-2"></i>
                        Index 'idx_featured_order': <?= $indexExists ? 'Exists' : 'Missing' ?>
                    </li>
                </ul>
            </div>
            
            <?php if (!$columnExists || !$indexExists): ?>
                <form method="POST" class="mt-6">
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
                        <p class="text-sm text-yellow-700">
                            <strong>Note:</strong> This migration will add the <code>featured_order</code> column to the products table 
                            and create an index for better performance. This is required for the featured product ordering feature.
                        </p>
                    </div>
                    
                    <button type="submit" name="run_migration" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                        <i class="fas fa-play mr-2"></i>Run Migration
                    </button>
                </form>
            <?php else: ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mt-6">
                    <p class="text-sm text-green-700">
                        <i class="fas fa-check-circle mr-2"></i>
                        <strong>Migration already completed.</strong> The database is up to date.
                    </p>
                </div>
            <?php endif; ?>
            
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="text-lg font-semibold mb-3">What this migration does:</h3>
                <ul class="list-disc list-inside space-y-2 text-sm text-gray-700">
                    <li>Adds <code>featured_order</code> column to the <code>products</code> table</li>
                    <li>Creates an index on <code>is_featured</code> and <code>featured_order</code> for better query performance</li>
                    <li>Sets default order values for existing featured products based on their ID</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
