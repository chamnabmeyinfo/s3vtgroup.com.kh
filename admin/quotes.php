<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

$message = '';
$error = '';

// Handle delete
if (!empty($_GET['delete'])) {
    try {
        $quoteId = (int)$_GET['delete'];
        
        // Validate ID
        if ($quoteId <= 0) {
            $error = 'Invalid quote request ID.';
        } else {
            // Check if quote exists
            $quote = db()->fetchOne(
                "SELECT id FROM quote_requests WHERE id = :id",
                ['id' => $quoteId]
            );
            
            if (!$quote) {
                $error = 'Quote request not found.';
            } else {
                // Delete quote
                $deleted = db()->delete('quote_requests', 'id = :id', ['id' => $quoteId]);
                if ($deleted > 0) {
                    $message = 'Quote request deleted successfully.';
                } else {
                    $error = 'Failed to delete quote request.';
                }
            }
        }
    } catch (\Exception $e) {
        $error = 'Error deleting quote request: ' . $e->getMessage();
    }
}

// Handle status update
if (!empty($_GET['update_status']) && !empty($_GET['id'])) {
    $status = $_GET['update_status'];
    $id = (int)$_GET['id'];
    
    if (in_array($status, ['pending', 'contacted', 'quoted', 'closed'])) {
        db()->update('quote_requests', ['status' => $status], 'id = :id', ['id' => $id]);
        $message = 'Status updated successfully.';
    }
}

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$sort = $_GET['sort'] ?? 'date_desc';

// Build query
$where = [];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = "q.status = :status";
    $params['status'] = $statusFilter;
}

if ($search) {
    $searchTerm = '%' . $search . '%';
    $where[] = "(q.name LIKE :search_name OR q.email LIKE :search_email OR q.phone LIKE :search_phone OR q.company LIKE :search_company OR p.name LIKE :search_product)";
    $params['search_name'] = $searchTerm;
    $params['search_email'] = $searchTerm;
    $params['search_phone'] = $searchTerm;
    $params['search_company'] = $searchTerm;
    $params['search_product'] = $searchTerm;
}

if ($dateFrom) {
    $where[] = "q.created_at >= :date_from";
    $params['date_from'] = $dateFrom . ' 00:00:00';
}

if ($dateTo) {
    $where[] = "q.created_at <= :date_to";
    $params['date_to'] = $dateTo . ' 23:59:59';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$quotes = db()->fetchAll(
    "SELECT q.*, p.name as product_name FROM quote_requests q 
     LEFT JOIN products p ON q.product_id = p.id 
     $whereClause
     ORDER BY q.created_at DESC",
    $params
);

// Sort quotes
switch ($sort) {
    case 'date_desc':
        usort($quotes, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        break;
    case 'date_asc':
        usort($quotes, fn($a, $b) => strtotime($a['created_at']) - strtotime($b['created_at']));
        break;
    case 'name_asc':
        usort($quotes, fn($a, $b) => strcmp($a['name'], $b['name']));
        break;
}

// Column visibility
$selectedColumns = $_GET['columns'] ?? ['date', 'name', 'email', 'product', 'status', 'actions'];
$availableColumns = [
    'date' => 'Date',
    'name' => 'Name',
    'email' => 'Email',
    'phone' => 'Phone',
    'company' => 'Company',
    'product' => 'Product',
    'status' => 'Status',
    'message' => 'Message',
    'actions' => 'Actions'
];

$pageTitle = 'Quote Requests';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/../includes/message.php';

// Setup filter component
$filterId = 'quotes-filter';
$filters = [
    'search' => true,
    'status' => [
        'options' => [
            'all' => 'All Statuses',
            'pending' => 'Pending',
            'contacted' => 'Contacted',
            'quoted' => 'Quoted',
            'closed' => 'Closed'
        ]
    ],
    'date_range' => true
];
$sortOptions = [
    'date_desc' => 'Newest First',
    'date_asc' => 'Oldest First',
    'name_asc' => 'Name (A-Z)'
];
$defaultColumns = ['date', 'name', 'email', 'product', 'status', 'actions'];
?>

<div class="w-full">
    <!-- Header -->
    <div class="bg-gradient-to-r from-yellow-600 to-amber-600 rounded-xl shadow-xl p-4 md:p-6 lg:p-8 mb-4 md:mb-6 text-white">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-1 md:mb-2">
                    <i class="fas fa-calculator mr-2 md:mr-3"></i>
                    Quote Requests
                </h1>
                <p class="text-yellow-100 text-sm md:text-lg">Manage customer quote requests</p>
            </div>
            <a href="<?= url('admin/quotes-export.php') ?>" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all w-full sm:w-auto text-center text-sm md:text-base">
                <i class="fas fa-download mr-2"></i>
                Export CSV
            </a>
        </div>
    </div>

    <?= displayMessage($message, $error) ?>
    
    <!-- Advanced Filters -->
    <?php include __DIR__ . '/includes/advanced-filters.php'; ?>
    
    <!-- Stats Bar -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-6">
                <div>
                    <span class="text-sm text-gray-600">Total Quotes:</span>
                    <span class="ml-2 font-bold text-gray-900"><?= count($quotes) ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quotes Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto -mx-4 md:mx-0">
            <div class="inline-block min-w-full align-middle">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                <tr>
                    <?php if (in_array('date', $selectedColumns) || empty($_GET['columns'])): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="date">Date</th>
                    <?php endif; ?>
                    
                    <?php if (in_array('name', $selectedColumns) || empty($_GET['columns'])): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="name">Name</th>
                    <?php endif; ?>
                    
                    <?php if (in_array('email', $selectedColumns) || empty($_GET['columns'])): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="email">Email</th>
                    <?php endif; ?>
                    
                    <?php if (in_array('phone', $selectedColumns)): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="phone">Phone</th>
                    <?php endif; ?>
                    
                    <?php if (in_array('company', $selectedColumns)): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="company">Company</th>
                    <?php endif; ?>
                    
                    <?php if (in_array('product', $selectedColumns) || empty($_GET['columns'])): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="product">Product</th>
                    <?php endif; ?>
                    
                    <?php if (in_array('status', $selectedColumns) || empty($_GET['columns'])): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="status">Status</th>
                    <?php endif; ?>
                    
                    <?php if (in_array('message', $selectedColumns)): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="message">Message</th>
                    <?php endif; ?>
                    
                    <?php if (in_array('actions', $selectedColumns) || empty($_GET['columns'])): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="actions">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($quotes)): ?>
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">No quote requests found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($quotes as $quote): ?>
                    <tr>
                        <?php if (in_array('date', $selectedColumns) || empty($_GET['columns'])): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-column="date">
                            <?= date('M d, Y', strtotime($quote['created_at'])) ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('name', $selectedColumns) || empty($_GET['columns'])): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" data-column="name">
                            <?= escape($quote['name']) ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('email', $selectedColumns) || empty($_GET['columns'])): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-column="email">
                            <a href="mailto:<?= escape($quote['email']) ?>" class="text-blue-600 hover:underline">
                                <?= escape($quote['email']) ?>
                            </a>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('phone', $selectedColumns)): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-column="phone">
                            <?= escape($quote['phone'] ?? '-') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('company', $selectedColumns)): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-column="company">
                            <?= escape($quote['company'] ?? '-') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('product', $selectedColumns) || empty($_GET['columns'])): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-column="product">
                            <?= escape($quote['product_name'] ?? 'General Inquiry') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('status', $selectedColumns) || empty($_GET['columns'])): ?>
                        <td class="px-6 py-4 whitespace-nowrap" data-column="status">
                            <select onchange="updateStatus(<?= $quote['id'] ?>, this.value)" 
                                    class="text-xs px-2 py-1 rounded border <?= 
                                        $quote['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                        ($quote['status'] === 'contacted' ? 'bg-blue-100 text-blue-800' :
                                        ($quote['status'] === 'quoted' ? 'bg-green-100 text-green-800' :
                                        'bg-gray-100 text-gray-800'))
                                    ?>">
                                <option value="pending" <?= $quote['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="contacted" <?= $quote['status'] === 'contacted' ? 'selected' : '' ?>>Contacted</option>
                                <option value="quoted" <?= $quote['status'] === 'quoted' ? 'selected' : '' ?>>Quoted</option>
                                <option value="closed" <?= $quote['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('message', $selectedColumns)): ?>
                        <td class="px-6 py-4 text-sm text-gray-500" data-column="message">
                            <div class="max-w-xs truncate" title="<?= escape($quote['message'] ?? '') ?>">
                                <?= escape(substr($quote['message'] ?? '', 0, 50)) ?>...
                            </div>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('actions', $selectedColumns) || empty($_GET['columns'])): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2" data-column="actions">
                            <a href="?view=<?= $quote['id'] ?>" 
                               onclick="event.preventDefault(); viewQuote(<?= $quote['id'] ?>);" 
                               class="text-blue-600 hover:text-blue-900" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="mailto:<?= escape($quote['email']) ?>?subject=Re: Quote Request" 
                               class="text-green-600 hover:text-green-900" title="Email">
                                <i class="fas fa-envelope"></i>
                            </a>
                            <a href="?delete=<?= $quote['id'] ?>" 
                               onclick="return confirm('Delete this quote request?')" 
                               class="text-red-600 hover:text-red-900" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(id, status) {
    window.location.href = '?update_status=' + status + '&id=' + id + '&' + new URLSearchParams(window.location.search).toString();
}

function viewQuote(id) {
    // Could open in modal or redirect to detail page
    alert('Quote detail view - ID: ' + id);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
