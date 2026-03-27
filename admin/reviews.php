<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

$message = '';
$error = '';

// Handle approve
if (!empty($_GET['approve'])) {
    db()->update('product_reviews', ['is_approved' => 1], 'id = :id', ['id' => (int)$_GET['approve']]);
    $message = 'Review approved successfully.';
}

// Handle delete
if (!empty($_GET['delete'])) {
    db()->delete('product_reviews', 'id = :id', ['id' => (int)$_GET['delete']]);
    $message = 'Review deleted successfully.';
}

$filter = $_GET['filter'] ?? 'all';
$where = [];
$params = [];

if ($filter === 'pending') {
    $where[] = "is_approved = 0";
} elseif ($filter === 'approved') {
    $where[] = "is_approved = 1";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$reviews = db()->fetchAll(
    "SELECT r.*, p.name as product_name, p.slug as product_slug 
     FROM product_reviews r
     LEFT JOIN products p ON r.product_id = p.id
     $whereClause
     ORDER BY r.created_at DESC",
    $params
);

$pageTitle = 'Product Reviews';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <!-- Header -->
    <div class="bg-gradient-to-r from-orange-600 to-amber-600 rounded-xl shadow-xl p-4 md:p-6 lg:p-8 mb-4 md:mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-1 md:mb-2">
                    <i class="fas fa-star mr-2 md:mr-3"></i>
                    Product Reviews
                </h1>
                <p class="text-orange-100 text-sm md:text-lg">Manage and moderate customer reviews</p>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white rounded-lg shadow-md p-2 mb-4 md:mb-6 flex flex-wrap gap-2">
        <a href="?filter=all" class="px-4 md:px-6 py-2 md:py-3 rounded-lg font-semibold transition-all text-sm md:text-base <?= $filter === 'all' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100' ?>">
            <i class="fas fa-list mr-2"></i> All Reviews
        </a>
        <a href="?filter=pending" class="px-4 md:px-6 py-2 md:py-3 rounded-lg font-semibold transition-all text-sm md:text-base <?= $filter === 'pending' ? 'bg-yellow-600 text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100' ?>">
            <i class="fas fa-clock mr-2"></i> Pending Approval
        </a>
        <a href="?filter=approved" class="px-4 md:px-6 py-2 md:py-3 rounded-lg font-semibold transition-all text-sm md:text-base <?= $filter === 'approved' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100' ?>">
            <i class="fas fa-check-circle mr-2"></i> Approved
        </a>
    </div>

    <!-- Reviews Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto -mx-4 md:mx-0">
            <div class="inline-block min-w-full align-middle">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reviewer</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rating</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comment</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($reviews as $review): ?>
            <tr>
                <td class="px-6 py-4">
                    <a href="<?= url('product.php?slug=' . escape($review['product_slug'])) ?>" target="_blank"
                       class="text-blue-600 hover:underline">
                        <?= escape($review['product_name']) ?>
                    </a>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium"><?= escape($review['name']) ?></div>
                    <div class="text-xs text-gray-500"><?= escape($review['email']) ?></div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star text-sm <?= $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>"></i>
                        <?php endfor; ?>
                        <span class="ml-2 text-sm"><?= $review['rating'] ?>/5</span>
                    </div>
                    <?php if ($review['title']): ?>
                        <div class="text-sm font-semibold mt-1"><?= escape($review['title']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                    <?= escape($review['comment']) ?>
                </td>
                <td class="px-6 py-4">
                    <?php if ($review['is_approved']): ?>
                        <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">Approved</span>
                    <?php else: ?>
                        <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-800">Pending</span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    <?= date('M d, Y', strtotime($review['created_at'])) ?>
                </td>
                <td class="px-6 py-4 text-sm font-medium space-x-2">
                    <?php if (!$review['is_approved']): ?>
                        <a href="?approve=<?= $review['id'] ?>" class="text-green-600 hover:text-green-900">
                            Approve
                        </a>
                    <?php endif; ?>
                    <button onclick="showReview('<?= escape(addslashes($review['comment'])) ?>')" 
                            class="text-blue-600 hover:text-blue-900">
                        View
                    </button>
                    <a href="?delete=<?= $review['id'] ?>" 
                       onclick="return confirm('Are you sure?')" 
                       class="text-red-600 hover:text-red-900">
                        Delete
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
            </div>
        </div>
    </div>
</div>

<div id="reviewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Full Review</h3>
            <button onclick="closeReview()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>
        <div id="reviewContent" class="text-gray-700"></div>
        <button onclick="closeReview()" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Close
        </button>
    </div>
</div>

<script>
function showReview(comment) {
    document.getElementById('reviewContent').textContent = comment;
    document.getElementById('reviewModal').classList.remove('hidden');
}

function closeReview() {
    document.getElementById('reviewModal').classList.add('hidden');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

