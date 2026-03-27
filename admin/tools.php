<?php
/**
 * Optional Tools - Custom Functions & Automation
 */
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

$message = '';
$messageType = '';
$output = '';

// Handle tool execution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_tool'])) {
    $toolId = $_POST['tool_id'] ?? '';
    $toolName = $_POST['tool_name'] ?? '';
    
    try {
        ob_start();
        
        // Include custom tools functions
        $customToolsFile = __DIR__ . '/../app/Tools/custom-tools.php';
        if (file_exists($customToolsFile)) {
            require_once $customToolsFile;
        }
        
        // Include tools configuration
        $toolsFile = __DIR__ . '/../config/tools.php';
        if (file_exists($toolsFile)) {
            $tools = require $toolsFile;
        } else {
            $tools = [];
        }
        
        // Find and execute the tool
        if (isset($tools[$toolId])) {
            $tool = $tools[$toolId];
            
            if (isset($tool['function']) && function_exists($tool['function'])) {
                // Execute custom function
                $result = call_user_func($tool['function']);
                $output = ob_get_clean();
                if ($result !== null) {
                    $output .= "\n" . (is_string($result) ? $result : print_r($result, true));
                }
                $message = "Tool '{$toolName}' executed successfully!";
                $messageType = 'success';
            } elseif (isset($tool['file'])) {
                // Include and execute file (relative to project root)
                $filePath = __DIR__ . '/../' . ltrim($tool['file'], '/');
                if (file_exists($filePath)) {
                    include $filePath;
                    $output = ob_get_clean();
                    $message = "Tool '{$toolName}' executed successfully!";
                    $messageType = 'success';
                } else {
                    ob_end_clean();
                    $message = "Tool file not found: {$tool['file']}";
                    $messageType = 'error';
                }
            } else {
                ob_end_clean();
                $message = "Tool '{$toolName}' configuration is invalid.";
                $messageType = 'error';
            }
        } else {
            ob_end_clean();
            $message = "Tool not found.";
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $output = ob_get_clean();
        $message = "Error executing tool: " . $e->getMessage();
        $messageType = 'error';
        $output .= "\n\nError: " . $e->getMessage();
    }
}

// Load tools configuration
$toolsFile = __DIR__ . '/../config/tools.php';
$tools = [];

if (file_exists($toolsFile)) {
    $tools = require $toolsFile;
} else {
    // Create default tools file
    $defaultTools = [
        'clear_cache' => [
            'name' => 'Clear Cache',
            'description' => 'Clear all cached data and temporary files',
            'category' => 'Maintenance',
            'icon' => 'fa-trash-alt',
            'function' => 'clearCacheTool'
        ],
        'optimize_database' => [
            'name' => 'Optimize Database',
            'description' => 'Optimize database tables for better performance',
            'category' => 'Maintenance',
            'icon' => 'fa-database',
            'function' => 'optimizeDatabaseTool'
        ],
        'clean_old_logs' => [
            'name' => 'Clean Old Logs',
            'description' => 'Remove log files older than 30 days',
            'category' => 'Maintenance',
            'icon' => 'fa-file-alt',
            'function' => 'cleanOldLogsTool'
        ],
        'regenerate_thumbnails' => [
            'name' => 'Regenerate Thumbnails',
            'description' => 'Regenerate all product image thumbnails',
            'category' => 'Images',
            'icon' => 'fa-images',
            'function' => 'regenerateThumbnailsTool'
        ],
        'update_product_slugs' => [
            'name' => 'Update Product Slugs',
            'description' => 'Regenerate SEO-friendly slugs for all products',
            'category' => 'SEO',
            'icon' => 'fa-link',
            'function' => 'updateProductSlugsTool'
        ],
        'check_broken_images' => [
            'name' => 'Check Broken Images',
            'description' => 'Find and report broken image links',
            'category' => 'Images',
            'icon' => 'fa-exclamation-triangle',
            'function' => 'checkBrokenImagesTool'
        ],
    ];
    
    // Create tools file
    $toolsContent = "<?php\nreturn " . var_export($defaultTools, true) . ";\n";
    file_put_contents($toolsFile, $toolsContent);
    $tools = $defaultTools;
}

// Group tools by category
$toolsByCategory = [];
foreach ($tools as $id => $tool) {
    $category = $tool['category'] ?? 'Other';
    if (!isset($toolsByCategory[$category])) {
        $toolsByCategory[$category] = [];
    }
    $toolsByCategory[$category][$id] = $tool;
}

$pageTitle = 'Optional Tools';
include __DIR__ . '/includes/header.php';
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Optional Tools</h1>
            <p class="text-gray-600 mt-1">Custom functions and automation tasks for your website</p>
        </div>
        <a href="<?= url('admin/tools-manage.php') ?>" class="btn-primary">
            <i class="fas fa-plus mr-2"></i> Manage Tools
        </a>
    </div>
    
    <?php if (!empty($message)): ?>
    <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300' ?>">
        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> mr-2"></i>
        <?= escape($message) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($output): ?>
    <div class="mb-6 bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm overflow-x-auto">
        <div class="flex justify-between items-center mb-2">
            <span class="text-gray-400">Output:</span>
            <button onclick="this.parentElement.parentElement.remove()" class="text-gray-500 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <pre class="whitespace-pre-wrap"><?= escape($output) ?></pre>
    </div>
    <?php endif; ?>
    
    <?php if (empty($tools)): ?>
    <div class="bg-white rounded-lg shadow-md p-8 text-center">
        <i class="fas fa-tools text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-bold text-gray-700 mb-2">No Tools Available</h3>
        <p class="text-gray-600 mb-4">Get started by adding your first custom tool.</p>
        <a href="<?= url('admin/tools-manage.php') ?>" class="btn-primary inline-block">
            <i class="fas fa-plus mr-2"></i> Add Tool
        </a>
    </div>
    <?php else: ?>
        <?php foreach ($toolsByCategory as $category => $categoryTools): ?>
        <div class="mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-folder mr-2 text-blue-600"></i>
                <?= escape($category) ?>
            </h2>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($categoryTools as $toolId => $tool): ?>
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas <?= escape($tool['icon'] ?? 'fa-cog') ?> text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800"><?= escape($tool['name']) ?></h3>
                            </div>
                        </div>
                    </div>
                    
                    <p class="text-gray-600 text-sm mb-4"><?= escape($tool['description'] ?? 'No description') ?></p>
                    
                    <?php if (isset($tool['warning']) && $tool['warning']): ?>
                    <div class="mb-4 p-2 bg-yellow-50 border border-yellow-200 rounded text-yellow-800 text-xs">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <?= escape($tool['warning']) ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" onsubmit="return confirm('Are you sure you want to execute this tool?');">
                        <input type="hidden" name="tool_id" value="<?= escape($toolId) ?>">
                        <input type="hidden" name="tool_name" value="<?= escape($tool['name']) ?>">
                        <button type="submit" name="execute_tool" class="w-full btn-primary">
                            <i class="fas fa-play mr-2"></i> Execute
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Info Section -->
    <div class="bg-blue-50 rounded-lg p-6 mt-8">
        <h3 class="text-lg font-bold text-blue-900 mb-2">
            <i class="fas fa-info-circle mr-2"></i>
            About Optional Tools
        </h3>
        <ul class="text-blue-800 space-y-2 list-disc list-inside">
            <li>Tools are stored in <code class="bg-blue-100 px-1 rounded">config/tools.php</code></li>
            <li>You can add custom functions or include external PHP files</li>
            <li>All tools run with admin privileges - use with caution</li>
            <li>Tool output is displayed after execution</li>
            <li>Use the "Manage Tools" button to add, edit, or remove tools</li>
        </ul>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

