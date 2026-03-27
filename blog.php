<?php
require_once __DIR__ . '/bootstrap/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if blog_posts table exists
$tableExists = false;
try {
    db()->fetchOne("SELECT 1 FROM blog_posts LIMIT 1");
    $tableExists = true;
} catch (\Exception $e) {
    $tableExists = false;
}

// Get blog posts
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 9;
$offset = ($page - 1) * $limit;
$category = $_GET['category'] ?? '';

$posts = [];
$totalPosts = 0;
$totalPages = 0;
$categories = [];

if ($tableExists) {
$where = "is_published = 1";
$params = [];

if ($category) {
    $where .= " AND category = :category";
    $params['category'] = $category;
}

    try {
$posts = db()->fetchAll(
    "SELECT * FROM blog_posts WHERE {$where} ORDER BY published_at DESC, created_at DESC LIMIT {$limit} OFFSET {$offset}",
    $params
);

$totalPosts = (int)db()->fetchOne("SELECT COUNT(*) as count FROM blog_posts WHERE {$where}", $params)['count'];
$totalPages = ceil($totalPosts / $limit);

// Get categories
$categories = db()->fetchAll("SELECT DISTINCT category FROM blog_posts WHERE is_published = 1 AND category IS NOT NULL AND category != '' ORDER BY category");
    } catch (\Exception $e) {
        // Table exists but query failed - show empty state
        $posts = [];
    }
}

$pageTitle = 'Blog & News - ' . get_site_name();
include __DIR__ . '/includes/header.php';
?>

<main class="py-12">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold mb-4">Blog & News</h1>
            <p class="text-gray-600 text-lg">Latest updates, tips, and insights about forklifts and industrial equipment</p>
        </div>
        
        <?php if (!$tableExists): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg mb-8">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 mt-1"></i>
                <div>
                    <h3 class="font-semibold text-yellow-800 mb-1">Blog Table Not Set Up</h3>
                    <p class="text-yellow-700 text-sm">The blog_posts table doesn't exist yet. Please import the database schema from <code class="bg-yellow-100 px-2 py-1 rounded">database/even-more-features.sql</code> to enable the blog feature.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="grid md:grid-cols-4 gap-8">
            <!-- Sidebar -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-20">
                    <h2 class="text-xl font-bold mb-4">Categories</h2>
                    <ul class="space-y-2">
                        <li>
                            <a href="<?= url('blog.php') ?>" 
                               class="block px-3 py-2 rounded hover:bg-blue-50 <?= !$category ? 'bg-blue-100 font-semibold' : '' ?>">
                                All Posts
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="<?= url('blog.php?category=' . urlencode($cat['category'])) ?>" 
                                   class="block px-3 py-2 rounded hover:bg-blue-50 <?= $category === $cat['category'] ? 'bg-blue-100 font-semibold' : '' ?>">
                                    <?= escape($cat['category']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Posts Grid -->
            <div class="md:col-span-3">
                <?php if (empty($posts)): ?>
                    <div class="bg-white rounded-lg shadow-md p-12 text-center">
                        <i class="fas fa-newspaper text-6xl text-gray-300 mb-4"></i>
                        <h2 class="text-2xl font-bold mb-2">No Posts Yet</h2>
                        <p class="text-gray-600">Check back soon for the latest updates!</p>
                    </div>
                <?php else: ?>
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <?php foreach ($posts as $post): ?>
                        <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow">
                            <?php if (!empty($post['featured_image'])): ?>
                                <a href="<?= url('blog-post.php?slug=' . escape($post['slug'])) ?>">
                                    <img src="<?= asset('storage/uploads/' . escape($post['featured_image'])) ?>" 
                                         alt="<?= escape($post['title']) ?>"
                                         class="w-full h-48 object-cover">
                                </a>
                            <?php endif; ?>
                            <div class="p-6">
                                <?php if (!empty($post['category'])): ?>
                                    <span class="text-xs text-blue-600 font-semibold uppercase"><?= escape($post['category']) ?></span>
                                <?php endif; ?>
                                <h2 class="text-xl font-bold mt-2 mb-3">
                                    <a href="<?= url('blog-post.php?slug=' . escape($post['slug'])) ?>" 
                                       class="hover:text-blue-600">
                                        <?= escape($post['title']) ?>
                                    </a>
                                </h2>
                                <p class="text-gray-600 mb-4 line-clamp-3"><?= escape($post['excerpt'] ?? '') ?></p>
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <span><?= date('M d, Y', strtotime($post['published_at'] ?? $post['created_at'])) ?></span>
                                    <a href="<?= url('blog-post.php?slug=' . escape($post['slug'])) ?>" 
                                       class="text-blue-600 hover:underline">
                                        Read More →
                                    </a>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="flex justify-center gap-2">
                        <?php if ($page > 1): ?>
                            <a href="<?= url('blog.php?page=' . ($page - 1) . ($category ? '&category=' . urlencode($category) : '')) ?>" 
                               class="px-4 py-2 border rounded-lg hover:bg-gray-50">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="<?= url('blog.php?page=' . $i . ($category ? '&category=' . urlencode($category) : '')) ?>" 
                               class="px-4 py-2 border rounded-lg <?= $i === $page ? 'bg-blue-600 text-white' : 'hover:bg-gray-50' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="<?= url('blog.php?page=' . ($page + 1) . ($category ? '&category=' . urlencode($category) : '')) ?>" 
                               class="px-4 py-2 border rounded-lg hover:bg-gray-50">Next</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

