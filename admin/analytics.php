<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

// Get statistics for last 30 days
$stats = [
    'products_added' => db()->fetchOne("SELECT COUNT(*) as count FROM products WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count'],
    'quotes_received' => db()->fetchOne("SELECT COUNT(*) as count FROM quote_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count'],
    'messages_received' => db()->fetchOne("SELECT COUNT(*) as count FROM contact_messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count'],
    'reviews_received' => db()->fetchOne("SELECT COUNT(*) as count FROM product_reviews WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count'],
    'newsletter_subscribers' => db()->fetchOne("SELECT COUNT(*) as count FROM newsletter_subscribers WHERE status = 'active'")['count'],
];

// Get daily stats for chart (last 7 days)
$dailyStats = db()->fetchAll(
    "SELECT 
        DATE(created_at) as date,
        COUNT(*) as count,
        'quotes' as type
     FROM quote_requests 
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(created_at)
     UNION ALL
     SELECT 
        DATE(created_at) as date,
        COUNT(*) as count,
        'messages' as type
     FROM contact_messages 
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(created_at)
     ORDER BY date DESC"
);

// Top products by views
$topProducts = db()->fetchAll(
    "SELECT id, name, view_count 
     FROM products 
     WHERE is_active = 1 
     ORDER BY view_count DESC 
     LIMIT 5"
);

// Products by category
$categoryStats = db()->fetchAll(
    "SELECT c.name, COUNT(p.id) as count 
     FROM categories c
     LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
     WHERE c.is_active = 1
     GROUP BY c.id, c.name
     ORDER BY count DESC"
);

$pageTitle = 'Analytics';
include __DIR__ . '/includes/header.php';
?>

<h1 class="text-3xl font-bold mb-6">Analytics & Reports</h1>

<!-- 30 Day Stats -->
<div class="grid md:grid-cols-5 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600 text-sm">Products Added</p>
        <p class="text-3xl font-bold text-blue-600"><?= $stats['products_added'] ?></p>
        <p class="text-xs text-gray-500 mt-1">Last 30 days</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600 text-sm">Quotes Received</p>
        <p class="text-3xl font-bold text-yellow-600"><?= $stats['quotes_received'] ?></p>
        <p class="text-xs text-gray-500 mt-1">Last 30 days</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600 text-sm">Messages</p>
        <p class="text-3xl font-bold text-red-600"><?= $stats['messages_received'] ?></p>
        <p class="text-xs text-gray-500 mt-1">Last 30 days</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600 text-sm">Reviews</p>
        <p class="text-3xl font-bold text-green-600"><?= $stats['reviews_received'] ?></p>
        <p class="text-xs text-gray-500 mt-1">Last 30 days</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600 text-sm">Newsletter</p>
        <p class="text-3xl font-bold text-purple-600"><?= $stats['newsletter_subscribers'] ?></p>
        <p class="text-xs text-gray-500 mt-1">Active subscribers</p>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <!-- Top Products -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">Top Products (Most Viewed)</h2>
        <div class="space-y-4">
            <?php foreach ($topProducts as $index => $product): ?>
            <div class="flex items-center justify-between border-b pb-3">
                <div class="flex items-center gap-3">
                    <span class="text-2xl font-bold text-gray-300"><?= $index + 1 ?></span>
                    <div>
                        <p class="font-semibold"><?= escape($product['name']) ?></p>
                        <p class="text-sm text-gray-600"><?= number_format($product['view_count']) ?> views</p>
                    </div>
                </div>
                <a href="<?= url('product.php?id=' . $product['id']) ?>" target="_blank" 
                   class="text-blue-600 hover:underline">View</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Products by Category -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">Products by Category</h2>
        <div class="space-y-4">
            <?php foreach ($categoryStats as $cat): ?>
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="font-semibold"><?= escape($cat['name']) ?></span>
                    <span class="text-blue-600 font-bold"><?= $cat['count'] ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" 
                         style="width: <?= max($categoryStats) ? ($cat['count'] / max(array_column($categoryStats, 'count')) * 100) : 0 ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

