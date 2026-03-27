<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Page;

$pageModel = new Page();
$message = '';
$error = '';
$page = null;
$pageId = $_GET['id'] ?? null;

// Check if pages table exists
$tableExists = false;
try {
    db()->fetchOne("SELECT 1 FROM pages LIMIT 1");
    $tableExists = true;
} catch (\Exception $e) {
    $tableExists = false;
}

if ($pageId) {
    $page = $pageModel->getById($pageId);
    if (!$page) {
        header('Location: ' . url('admin/pages.php'));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tableExists) {
    $data = [
        'title' => trim($_POST['title'] ?? ''),
        'slug' => trim($_POST['slug'] ?? ''),
        'content' => $_POST['content'] ?? '',
        'meta_title' => trim($_POST['meta_title'] ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
    
    if (empty($data['title'])) {
        $error = 'Page title is required.';
    } else {
        try {
            if ($pageId) {
                $updated = $pageModel->update($pageId, $data);
                if ($updated) {
                    $message = 'Page updated successfully.';
                    $page = $pageModel->getById($pageId);
                } else {
                    $error = 'Failed to update page.';
                }
            } else {
                $newId = $pageModel->create($data);
                if ($newId) {
                    $message = 'Page created successfully.';
                    header('Location: ' . url('admin/pages.php'));
                    exit;
                } else {
                    $error = 'Failed to create page.';
                }
            }
        } catch (\Exception $e) {
            $error = 'Error saving page: ' . $e->getMessage();
        }
    }
}

$pageTitle = $page ? 'Edit Page' : 'Add New Page';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 rounded-xl shadow-xl p-4 md:p-6 lg:p-8 mb-4 md:mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-1 md:mb-2">
                    <i class="fas fa-file-alt mr-2 md:mr-3"></i>
                    <?= $page ? 'Edit Page' : 'Add New Page' ?>
                </h1>
                <p class="text-gray-200 text-sm md:text-lg">Create and manage site pages</p>
            </div>
            <a href="<?= url('admin/pages.php') ?>" class="bg-white/20 text-white px-4 py-2 rounded-lg font-semibold hover:bg-white/30 transition-all">
                <i class="fas fa-arrow-left mr-2"></i>Back to Pages
            </a>
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
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2 text-xl"></i>
            <span class="font-semibold"><?= escape($message) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2 text-xl"></i>
            <span class="font-semibold"><?= escape($error) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($tableExists): ?>
    <form method="POST" class="bg-white rounded-xl shadow-lg p-4 md:p-6 lg:p-8 space-y-6">
        <!-- Basic Information -->
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-heading text-gray-400 mr-2"></i> Page Title *
                </label>
                <input type="text" name="title" required value="<?= escape($page['title'] ?? '') ?>"
                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                       placeholder="Enter page title">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-link text-gray-400 mr-2"></i> Slug
                </label>
                <input type="text" name="slug" value="<?= escape($page['slug'] ?? '') ?>"
                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                       placeholder="auto-generated-from-title">
                <p class="text-xs text-gray-500 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>
                    Leave empty to auto-generate from title. URL: <code class="bg-gray-100 px-1 rounded">page.php?slug=your-slug</code>
                </p>
            </div>
        </div>

        <!-- Page Content -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-file-alt text-gray-400 mr-2"></i> Page Content *
            </label>
            <textarea name="content" id="pageContent" rows="15" required
                      class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all font-mono text-sm"
                      placeholder="Enter page content (HTML supported)"><?= escape($page['content'] ?? '') ?></textarea>
            <p class="text-xs text-gray-500 mt-1">
                <i class="fas fa-info-circle mr-1"></i>
                You can use HTML tags for formatting. The content will be displayed on the frontend page.
            </p>
        </div>

        <!-- SEO Settings -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border-2 border-blue-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-search text-blue-600 mr-2"></i>SEO Settings (Optional)
            </h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Meta Title</label>
                    <input type="text" name="meta_title" value="<?= escape($page['meta_title'] ?? '') ?>"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                           placeholder="SEO title (appears in browser tab)">
                    <p class="text-xs text-gray-500 mt-1">If empty, page title will be used</p>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Meta Description</label>
                    <textarea name="meta_description" rows="3"
                              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                              placeholder="SEO description (appears in search results)"><?= escape($page['meta_description'] ?? '') ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">Recommended: 150-160 characters for best SEO results</p>
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="bg-gray-50 rounded-lg p-4">
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" name="is_active" <?= ($page['is_active'] ?? 1) ? 'checked' : '' ?>
                       class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500">
                <span class="ml-3 text-sm font-semibold text-gray-700">Active (Show on website)</span>
            </label>
            <p class="text-xs text-gray-500 mt-2 ml-8">
                <i class="fas fa-info-circle mr-1"></i>
                Inactive pages won't be visible on the frontend but can still be accessed via direct URL if you know the slug.
            </p>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex gap-4 pt-4 border-t border-gray-200">
            <button type="submit" class="bg-gradient-to-r from-green-600 to-emerald-600 text-white px-8 py-3 rounded-lg font-semibold hover:from-green-700 hover:to-emerald-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                <i class="fas fa-save mr-2"></i>
                <?= $page ? 'Update Page' : 'Create Page' ?>
            </button>
            <a href="<?= url('admin/pages.php') ?>" class="bg-gray-200 text-gray-700 px-8 py-3 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <?php if ($page): ?>
            <a href="<?= url('page.php?slug=' . urlencode($page['slug'])) ?>" target="_blank" 
               class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                <i class="fas fa-eye mr-2"></i>Preview
            </a>
            <?php endif; ?>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
