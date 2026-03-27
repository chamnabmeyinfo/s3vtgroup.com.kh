<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Page;

$pageModel = new Page();
$message = '';
$error = '';

// Check if pages table exists
$tableExists = false;
try {
    db()->fetchOne("SELECT 1 FROM pages LIMIT 1");
    $tableExists = true;
} catch (\Exception $e) {
    $tableExists = false;
}

// Handle delete
if (!empty($_GET['delete'])) {
    try {
        $pageId = (int)$_GET['delete'];
        
        if ($pageId <= 0) {
            $error = 'Invalid page ID.';
        } else {
            $page = $pageModel->getById($pageId);
            if (!$page) {
                $error = 'Page not found.';
            } else {
                $deleted = $pageModel->delete($pageId);
                if ($deleted) {
                    $message = 'Page deleted successfully.';
                } else {
                    $error = 'Failed to delete page.';
                }
            }
        }
    } catch (\Exception $e) {
        $error = 'Error deleting page: ' . $e->getMessage();
    }
}

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

// Get all pages
$pages = [];
if ($tableExists) {
    $pages = $pageModel->getAll(false);
    
    // Apply filters
    if ($search) {
        $pages = array_filter($pages, function($page) use ($search) {
            return stripos($page['title'], $search) !== false || 
                   stripos($page['slug'], $search) !== false ||
                   stripos($page['content'] ?? '', $search) !== false;
        });
    }
    
    if ($statusFilter === 'active') {
        $pages = array_filter($pages, fn($p) => $p['is_active'] == 1);
    } elseif ($statusFilter === 'inactive') {
        $pages = array_filter($pages, fn($p) => $p['is_active'] == 0);
    }
    
    // Re-index array after filtering
    $pages = array_values($pages);
}

$pageTitle = 'Manage Pages';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full p-4 md:p-6">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-xl p-4 md:p-6 mb-6 text-white">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-2">
                    <i class="fas fa-file-alt mr-2"></i>Pages
                </h1>
                <p class="text-blue-100">Manage your site pages</p>
            </div>
            <div class="flex gap-3">
                <a href="<?= url('admin/page-edit.php') ?>" class="bg-white/20 text-white px-4 py-2 rounded-lg font-semibold hover:bg-white/30 transition-all">
                    <i class="fas fa-plus mr-2"></i>Add New Page
                </a>
            </div>
        </div>
    </div>

    <?php if (!$tableExists): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg mb-6">
        <div class="flex items-start">
            <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 mt-1"></i>
            <div class="flex-1">
                <h3 class="font-semibold text-yellow-800 mb-2">Pages Table Not Set Up</h3>
                <p class="text-yellow-700 text-sm mb-4">The pages table doesn't exist yet. Please import the database schema to enable page management.</p>
                <div class="bg-yellow-100 p-4 rounded">
                    <p class="text-sm font-semibold mb-2">To set up pages:</p>
                    <ol class="list-decimal list-inside text-sm space-y-1">
                        <li>Import <code class="bg-white px-2 py-1 rounded">database/create-pages-table.sql</code> (recommended)</li>
                        <li>Or import <code class="bg-white px-2 py-1 rounded">database/even-more-features.sql</code> (includes other features too)</li>
                        <li>Run the SQL file via phpMyAdmin/cPanel MySQL</li>
                        <li>Refresh this page after importing</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
        <i class="fas fa-check-circle mr-2"></i><?= escape($message) ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6">
        <i class="fas fa-exclamation-circle mr-2"></i><?= escape($error) ?>
    </div>
    <?php endif; ?>

    <?php if ($tableExists): ?>
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <form method="GET" class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                <input type="text" name="search" value="<?= escape($search) ?>" 
                       placeholder="Search pages..." 
                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-all">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                <a href="<?= url('admin/pages.php') ?>" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-300 transition-all">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Pages Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <?php if (empty($pages)): ?>
            <div class="p-12 text-center">
                <i class="fas fa-file-alt text-gray-300 text-6xl mb-4"></i>
                <p class="text-gray-500 text-lg mb-4">No pages found.</p>
                <a href="<?= url('admin/page-edit.php') ?>" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-all">
                    <i class="fas fa-plus mr-2"></i>Create Your First Page
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($pages as $page): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <i class="fas fa-file-alt text-blue-600 mr-3"></i>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900"><?= escape($page['title']) ?></div>
                                        <?php if (!empty($page['meta_title'])): ?>
                                        <div class="text-xs text-gray-500">Meta: <?= escape($page['meta_title']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <code class="text-xs bg-gray-100 px-2 py-1 rounded"><?= escape($page['slug']) ?></code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($page['is_active']): ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>Active
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                        <i class="fas fa-times-circle mr-1"></i>Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('M d, Y', strtotime($page['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    <a href="<?= url('page.php?slug=' . urlencode($page['slug'])) ?>" target="_blank" 
                                       class="action-btn action-btn-view" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= url('admin/page-edit.php?id=' . $page['id']) ?>" 
                                       class="action-btn action-btn-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" 
                                       onclick="deletePage(<?= $page['id'] ?>); return false;"
                                       class="action-btn action-btn-delete" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
async function deletePage(pageId) {
    const confirmed = await customConfirm('Are you sure you want to delete this page?', 'Delete Page');
    if (confirmed) {
        window.location.href = '?delete=' + pageId;
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
