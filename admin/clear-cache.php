<?php
/**
 * Cache Clearing Utility for Admin
 * Use this to clear OPcache and verify files are updated
 */

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cache'])) {
    try {
        $cleared = [];
        
        // Clear OPcache if available
        if (function_exists('opcache_reset')) {
            if (opcache_reset()) {
                $cleared[] = 'OPcache';
            }
        }
        
        // Clear APCu cache if available
        if (function_exists('apcu_clear_cache')) {
            if (apcu_clear_cache()) {
                $cleared[] = 'APCu cache';
            }
        }
        
        // Clear file-based cache
        $cacheDir = __DIR__ . '/../storage/cache';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            foreach ($files as $file) {
                if (is_file($file) && basename($file) !== '.gitkeep') {
                    @unlink($file);
                }
            }
            $cleared[] = 'File cache';
        }
        
        if (!empty($cleared)) {
            $message = 'Cache cleared successfully: ' . implode(', ', $cleared);
        } else {
            $message = 'No cache systems found or already cleared.';
        }
    } catch (Exception $e) {
        $error = 'Error clearing cache: ' . $e->getMessage();
    }
}

// Check file modification time
$productsFile = __DIR__ . '/products.php';
$fileModified = file_exists($productsFile) ? date('Y-m-d H:i:s', filemtime($productsFile)) : 'File not found';
$fileSize = file_exists($productsFile) ? number_format(filesize($productsFile) / 1024, 2) . ' KB' : 'N/A';

$pageTitle = 'Clear Cache';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">
            <i class="fas fa-broom mr-3 text-indigo-600"></i>
            Clear Cache
        </h1>
        
        <?php if (!empty($message)): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded-lg mb-6">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                <span class="font-semibold"><?= escape($message) ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-lg mb-6">
            <div class="flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i>
                <span class="font-semibold"><?= escape($error) ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- File Information -->
        <div class="bg-gray-50 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                File Information
            </h2>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-gray-600 mb-1">Products File Location:</div>
                    <div class="font-mono text-sm bg-white p-2 rounded border"><?= escape($productsFile) ?></div>
                </div>
                <div>
                    <div class="text-sm text-gray-600 mb-1">Last Modified:</div>
                    <div class="font-semibold text-gray-900"><?= escape($fileModified) ?></div>
                </div>
                <div>
                    <div class="text-sm text-gray-600 mb-1">File Size:</div>
                    <div class="font-semibold text-gray-900"><?= escape($fileSize) ?></div>
                </div>
                <div>
                    <div class="text-sm text-gray-600 mb-1">File Exists:</div>
                    <div class="font-semibold <?= file_exists($productsFile) ? 'text-green-600' : 'text-red-600' ?>">
                        <?= file_exists($productsFile) ? 'Yes' : 'No' ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Cache Information -->
        <div class="bg-gray-50 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-server mr-2 text-blue-600"></i>
                Cache Status
            </h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-white rounded-lg border">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-memory text-indigo-600"></i>
                        <span class="font-medium">OPcache</span>
                    </div>
                    <span class="px-3 py-1 rounded-full text-sm font-medium <?= function_exists('opcache_reset') ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                        <?= function_exists('opcache_reset') ? 'Enabled' : 'Not Available' ?>
                    </span>
                </div>
                <div class="flex items-center justify-between p-3 bg-white rounded-lg border">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-database text-indigo-600"></i>
                        <span class="font-medium">APCu Cache</span>
                    </div>
                    <span class="px-3 py-1 rounded-full text-sm font-medium <?= function_exists('apcu_clear_cache') ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                        <?= function_exists('apcu_clear_cache') ? 'Enabled' : 'Not Available' ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Clear Cache Form -->
        <form method="POST" class="mb-6">
            <button type="submit" name="clear_cache" 
                    class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-4 rounded-xl font-bold text-lg hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                <i class="fas fa-broom mr-2"></i>
                Clear All Cache
            </button>
        </form>
        
        <!-- Instructions -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
            <h3 class="font-semibold text-blue-900 mb-2">
                <i class="fas fa-lightbulb mr-2"></i>
                Troubleshooting Steps:
            </h3>
            <ol class="list-decimal list-inside space-y-2 text-blue-800 text-sm">
                <li>Click "Clear All Cache" button above</li>
                <li>Hard refresh your browser: <strong>Ctrl+Shift+R</strong> (Windows) or <strong>Cmd+Shift+R</strong> (Mac)</li>
                <li>Or open the page in an incognito/private window</li>
                <li>Check the "Last Modified" time above - it should match when you pulled from Git</li>
                <li>If still not working, contact your hosting provider to clear server-side cache</li>
            </ol>
        </div>
        
        <!-- Quick Links -->
        <div class="mt-6 flex gap-3">
            <a href="<?= url('admin/products.php') ?>" class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-lg text-center font-medium hover:bg-indigo-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Products
            </a>
            <a href="<?= url('admin/products.php') ?>?v=<?= time() ?>" class="flex-1 bg-purple-600 text-white px-4 py-2 rounded-lg text-center font-medium hover:bg-purple-700 transition-colors">
                <i class="fas fa-sync mr-2"></i>
                Force Refresh Products Page
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
