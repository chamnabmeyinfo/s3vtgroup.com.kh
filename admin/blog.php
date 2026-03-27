<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Manage Blog Posts';
include __DIR__ . '/includes/header.php';

// Check if blog_posts table exists
$tableExists = false;
try {
    db()->fetchOne("SELECT 1 FROM blog_posts LIMIT 1");
    $tableExists = true;
} catch (\Exception $e) {
    $tableExists = false;
}

// Get all blog posts
$posts = [];
if ($tableExists) {
    try {
$posts = db()->fetchAll("SELECT * FROM blog_posts ORDER BY created_at DESC");
    } catch (\Exception $e) {
        $posts = [];
    }
}
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Blog Posts</h1>
        <?php if ($tableExists): ?>
        <a href="blog-edit.php" class="btn-primary">
            <i class="fas fa-plus mr-2"></i> Add New Post
        </a>
        <?php endif; ?>
    </div>
    
    <?php if (!$tableExists): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg mb-6">
        <div class="flex items-start">
            <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 mt-1"></i>
            <div class="flex-1">
                <h3 class="font-semibold text-yellow-800 mb-2">Blog Table Not Set Up</h3>
                <p class="text-yellow-700 text-sm mb-4">The blog_posts table doesn't exist yet. Please import the database schema to enable blog management.</p>
                <div class="bg-yellow-100 p-4 rounded">
                    <p class="text-sm font-semibold mb-2">To set up the blog:</p>
                    <ol class="list-decimal list-inside text-sm space-y-1">
                        <li>Import <code class="bg-white px-2 py-1 rounded">database/even-more-features.sql</code></li>
                        <li>Or run the SQL file via phpMyAdmin/cPanel MySQL</li>
                        <li>Refresh this page after importing</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Views</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Published</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!$tableExists): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            <i class="fas fa-database text-gray-400 text-3xl mb-2 block"></i>
                            Blog table not set up. Please import the database schema first.
                        </td>
                    </tr>
                <?php elseif (empty($posts)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No blog posts found. <a href="blog-edit.php" class="text-blue-600 hover:underline">Add your first post</a></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium"><?= escape($post['title']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?= escape($post['category'] ?? '-') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?= escape($post['view_count']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?= $post['published_at'] ? date('M d, Y', strtotime($post['published_at'])) : '-' ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded <?= $post['is_published'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= $post['is_published'] ? 'Published' : 'Draft' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                            <a href="blog-edit.php?id=<?= $post['id'] ?>" class="text-blue-600 hover:underline">Edit</a>
                            <?php if ($post['is_published']): ?>
                                <a href="<?= url('blog-post.php?slug=' . escape($post['slug'])) ?>" target="_blank" class="text-green-600 hover:underline">View</a>
                            <?php endif; ?>
                            <a href="blog-delete.php?id=<?= $post['id'] ?>" onclick="return confirm('Delete this post?')" class="text-red-600 hover:underline">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

