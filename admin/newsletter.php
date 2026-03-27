<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

$message = '';
$error = '';

// Handle export
if (!empty($_GET['export'])) {
    $subscribers = db()->fetchAll("SELECT * FROM newsletter_subscribers WHERE status = 'active' ORDER BY subscribed_at DESC");
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="newsletter_subscribers_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['Email', 'Name', 'Subscribed At', 'Status']);
    
    foreach ($subscribers as $sub) {
        fputcsv($output, [
            $sub['email'],
            $sub['name'] ?? '',
            $sub['subscribed_at'],
            $sub['status']
        ]);
    }
    
    fclose($output);
    exit;
}

// Handle unsubscribe
if (!empty($_GET['unsubscribe'])) {
    db()->update('newsletter_subscribers', 
        ['status' => 'unsubscribed', 'unsubscribed_at' => date('Y-m-d H:i:s')], 
        'id = :id', 
        ['id' => (int)$_GET['unsubscribe']]
    );
    $message = 'Subscriber unsubscribed successfully.';
}

// Handle delete
if (!empty($_GET['delete'])) {
    db()->delete('newsletter_subscribers', 'id = :id', ['id' => (int)$_GET['delete']]);
    $message = 'Subscriber deleted successfully.';
}

$statusFilter = $_GET['status'] ?? 'active';
// Security fix: Use parameterized query to prevent SQL injection
$where = '';
$params = [];
if ($statusFilter !== 'all') {
    // Validate status to prevent injection
    $allowedStatuses = ['active', 'unsubscribed'];
    if (in_array($statusFilter, $allowedStatuses)) {
        $where = "WHERE status = :status";
        $params['status'] = $statusFilter;
    } else {
        // Default to active if invalid status provided
        $where = "WHERE status = :status";
        $params['status'] = 'active';
    }
}

$subscribers = db()->fetchAll(
    "SELECT * FROM newsletter_subscribers $where ORDER BY subscribed_at DESC",
    $params
);

$stats = [
    'total' => db()->fetchOne("SELECT COUNT(*) as count FROM newsletter_subscribers")['count'],
    'active' => db()->fetchOne("SELECT COUNT(*) as count FROM newsletter_subscribers WHERE status = 'active'")['count'],
    'unsubscribed' => db()->fetchOne("SELECT COUNT(*) as count FROM newsletter_subscribers WHERE status = 'unsubscribed'")['count'],
];

$pageTitle = 'Newsletter Subscribers';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-xl p-4 md:p-6 lg:p-8 mb-4 md:mb-6 text-white">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-1 md:mb-2">
                    <i class="fas fa-paper-plane mr-2 md:mr-3"></i>
                    Newsletter Subscribers
                </h1>
                <p class="text-indigo-100 text-sm md:text-lg">Manage your newsletter subscriber list</p>
            </div>
            <a href="?export=1" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all w-full sm:w-auto text-center text-sm md:text-base">
                <i class="fas fa-download mr-2"></i>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6 mb-4 md:mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white/20 rounded-lg p-3">
                    <i class="fas fa-users text-2xl"></i>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold"><?= $stats['total'] ?></div>
                    <div class="text-blue-100 text-sm">Total</div>
                </div>
            </div>
            <div class="text-blue-100 text-sm font-medium">Subscribers</div>
        </div>
        
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white/20 rounded-lg p-3">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold"><?= $stats['active'] ?></div>
                    <div class="text-green-100 text-sm">Active</div>
                </div>
            </div>
            <div class="text-green-100 text-sm font-medium">Active Subscribers</div>
        </div>
        
        <div class="bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white/20 rounded-lg p-3">
                    <i class="fas fa-ban text-2xl"></i>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold"><?= $stats['unsubscribed'] ?></div>
                    <div class="text-gray-100 text-sm">Unsubscribed</div>
                </div>
            </div>
            <div class="text-gray-100 text-sm font-medium">Unsubscribed</div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white rounded-lg shadow-md p-2 mb-4 md:mb-6 flex flex-wrap gap-2">
        <a href="?status=all" class="px-4 md:px-6 py-2 md:py-3 rounded-lg font-semibold transition-all text-sm md:text-base <?= $statusFilter === 'all' ? 'bg-indigo-600 text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100' ?>">
            <i class="fas fa-list mr-2"></i> All
        </a>
        <a href="?status=active" class="px-4 md:px-6 py-2 md:py-3 rounded-lg font-semibold transition-all text-sm md:text-base <?= $statusFilter === 'active' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100' ?>">
            <i class="fas fa-check-circle mr-2"></i> Active
        </a>
        <a href="?status=unsubscribed" class="px-4 md:px-6 py-2 md:py-3 rounded-lg font-semibold transition-all text-sm md:text-base <?= $statusFilter === 'unsubscribed' ? 'bg-gray-600 text-white shadow-lg' : 'text-gray-600 hover:bg-gray-100' ?>">
            <i class="fas fa-ban mr-2"></i> Unsubscribed
        </a>
    </div>

    <!-- Subscribers Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto -mx-4 md:mx-0">
            <div class="inline-block min-w-full align-middle">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subscribed</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($subscribers as $sub): ?>
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"><?= escape($sub['email']) ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= escape($sub['name'] ?? '') ?></td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs rounded <?= $sub['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                        <?= ucfirst($sub['status']) ?>
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?= date('M d, Y', strtotime($sub['subscribed_at'])) ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                    <?php if ($sub['status'] === 'active'): ?>
                        <a href="?unsubscribe=<?= $sub['id'] ?>" class="text-yellow-600 hover:text-yellow-900">
                            Unsubscribe
                        </a>
                    <?php endif; ?>
                    <a href="?delete=<?= $sub['id'] ?>" 
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

<?php include __DIR__ . '/includes/footer.php'; ?>

