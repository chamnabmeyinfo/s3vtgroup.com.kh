<?php
/**
 * Manage Optional Tools
 */
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

$message = '';
$messageType = '';

$toolsFile = __DIR__ . '/../config/tools.php';
$tools = [];

if (file_exists($toolsFile)) {
    $tools = require $toolsFile;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $newTool = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'category' => trim($_POST['category'] ?? 'Other'),
            'icon' => trim($_POST['icon'] ?? 'fa-cog'),
            'function' => trim($_POST['function'] ?? ''),
            'file' => trim($_POST['file'] ?? ''),
            'warning' => trim($_POST['warning'] ?? ''),
        ];
        
        $toolId = strtolower(str_replace([' ', '-'], '_', $newTool['name']));
        
        if (empty($newTool['name'])) {
            $message = 'Tool name is required.';
            $messageType = 'error';
        } elseif (isset($tools[$toolId])) {
            $message = 'A tool with this name already exists.';
            $messageType = 'error';
        } else {
            $tools[$toolId] = $newTool;
            $toolsContent = "<?php\nreturn " . var_export($tools, true) . ";\n";
            file_put_contents($toolsFile, $toolsContent);
            $message = 'Tool added successfully!';
            $messageType = 'success';
        }
    } elseif ($action === 'delete') {
        $toolId = $_POST['tool_id'] ?? '';
        if (isset($tools[$toolId])) {
            unset($tools[$toolId]);
            $toolsContent = "<?php\nreturn " . var_export($tools, true) . ";\n";
            file_put_contents($toolsFile, $toolsContent);
            $message = 'Tool deleted successfully!';
            $messageType = 'success';
        }
    }
}

$pageTitle = 'Manage Tools';
include __DIR__ . '/includes/header.php';
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Manage Tools</h1>
            <p class="text-gray-600 mt-1">Add, edit, or remove custom tools</p>
        </div>
        <a href="<?= url('admin/tools.php') ?>" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Back to Tools
        </a>
    </div>
    
    <?php if (!empty($message)): ?>
    <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300' ?>">
        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> mr-2"></i>
        <?= escape($message) ?>
    </div>
    <?php endif; ?>
    
    <!-- Add New Tool Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">
            <i class="fas fa-plus-circle mr-2 text-blue-600"></i>
            Add New Tool
        </h2>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add">
            
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Tool Name *</label>
                    <input type="text" name="name" required 
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="e.g., Clear Cache">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Category</label>
                    <select name="category" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="Maintenance">Maintenance</option>
                        <option value="Images">Images</option>
                        <option value="SEO">SEO</option>
                        <option value="Database">Database</option>
                        <option value="Files">Files</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Description</label>
                <textarea name="description" rows="2"
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="What does this tool do?"></textarea>
            </div>
            
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Icon (FontAwesome class)</label>
                    <input type="text" name="icon" value="fa-cog"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="fa-cog, fa-trash, fa-database, etc.">
                    <p class="text-xs text-gray-500 mt-1">Use FontAwesome icon class (e.g., fa-cog, fa-trash-alt)</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Warning Message (Optional)</label>
                    <input type="text" name="warning"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="e.g., This will delete all cached files">
                </div>
            </div>
            
            <div class="border-t pt-4">
                <h3 class="font-semibold mb-2">Execution Method (Choose one):</h3>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Custom Function Name</label>
                        <input type="text" name="function"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                               placeholder="e.g., myCustomFunction">
                        <p class="text-xs text-gray-500 mt-1">Function must be defined in <code>app/Tools/custom-tools.php</code></p>
                    </div>
                    
                    <div class="text-center text-gray-500">OR</div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">PHP File Path (relative to project root)</label>
                        <input type="text" name="file"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                               placeholder="e.g., app/Tools/my-tool.php">
                        <p class="text-xs text-gray-500 mt-1">File will be included and executed</p>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i> Add Tool
            </button>
        </form>
    </div>
    
    <!-- Existing Tools List -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">
            <i class="fas fa-list mr-2 text-blue-600"></i>
            Existing Tools
        </h2>
        
        <?php if (empty($tools)): ?>
        <p class="text-gray-600 text-center py-8">No tools configured yet.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Category</th>
                        <th class="px-4 py-3 text-left">Description</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tools as $toolId => $tool): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <i class="fas <?= escape($tool['icon'] ?? 'fa-cog') ?> text-blue-600 mr-2"></i>
                                <span class="font-medium"><?= escape($tool['name']) ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">
                                <?= escape($tool['category'] ?? 'Other') ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <?= escape($tool['description'] ?? '-') ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            <?php if (!empty($tool['function'])): ?>
                                <code class="bg-gray-100 px-2 py-1 rounded">Function</code>
                            <?php elseif (!empty($tool['file'])): ?>
                                <code class="bg-gray-100 px-2 py-1 rounded">File</code>
                            <?php else: ?>
                                <span class="text-red-600">Invalid</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this tool?');" class="inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="tool_id" value="<?= escape($toolId) ?>">
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

