<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

$message = '';
$successMessage = '';

// Handle mark as read
if (!empty($_GET['mark_read'])) {
    db()->update('contact_messages', ['is_read' => 1], 'id = :id', ['id' => (int)$_GET['mark_read']]);
    $successMessage = 'Message marked as read.';
}

// Handle delete
if (!empty($_GET['delete'])) {
    try {
        $messageId = (int)$_GET['delete'];
        
        // Validate ID
        if ($messageId <= 0) {
            $successMessage = '';
            $message = 'Invalid message ID.';
        } else {
            // Check if message exists
            $msg = db()->fetchOne(
                "SELECT id FROM contact_messages WHERE id = :id",
                ['id' => $messageId]
            );
            
            if (!$msg) {
                $successMessage = '';
                $message = 'Message not found.';
            } else {
                // Delete message
                $deleted = db()->delete('contact_messages', 'id = :id', ['id' => $messageId]);
                if ($deleted > 0) {
                    $successMessage = 'Message deleted successfully.';
                    $message = '';
                } else {
                    $successMessage = '';
                    $message = 'Failed to delete message.';
                }
            }
        }
    } catch (\Exception $e) {
        $successMessage = '';
        $message = 'Error deleting message: ' . $e->getMessage();
    }
}

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$readFilter = $_GET['read'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$sort = $_GET['sort'] ?? 'date_desc';

// Build query
$where = [];
$params = [];

if ($readFilter === 'unread') {
    $where[] = "is_read = 0";
} elseif ($readFilter === 'read') {
    $where[] = "is_read = 1";
}

if ($search) {
    $searchTerm = '%' . $search . '%';
    $where[] = "(name LIKE :search_name OR email LIKE :search_email OR phone LIKE :search_phone OR subject LIKE :search_subject OR message LIKE :search_message)";
    $params['search_name'] = $searchTerm;
    $params['search_email'] = $searchTerm;
    $params['search_phone'] = $searchTerm;
    $params['search_subject'] = $searchTerm;
    $params['search_message'] = $searchTerm;
}

if ($dateFrom) {
    $where[] = "created_at >= :date_from";
    $params['date_from'] = $dateFrom . ' 00:00:00';
}

if ($dateTo) {
    $where[] = "created_at <= :date_to";
    $params['date_to'] = $dateTo . ' 23:59:59';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$messages = db()->fetchAll(
    "SELECT * FROM contact_messages $whereClause ORDER BY created_at DESC",
    $params
);

// Sort messages
switch ($sort) {
    case 'date_desc':
        usort($messages, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        break;
    case 'date_asc':
        usort($messages, fn($a, $b) => strtotime($a['created_at']) - strtotime($b['created_at']));
        break;
    case 'name_asc':
        usort($messages, fn($a, $b) => strcmp($a['name'], $b['name']));
        break;
}

// Column visibility
$selectedColumns = $_GET['columns'] ?? ['date', 'name', 'email', 'subject', 'status', 'actions'];
$availableColumns = [
    'date' => 'Date',
    'name' => 'Name',
    'email' => 'Email',
    'phone' => 'Phone',
    'subject' => 'Subject',
    'message' => 'Message',
    'status' => 'Status',
    'actions' => 'Actions'
];

$pageTitle = 'Contact Messages';
include __DIR__ . '/includes/header.php';

// Setup filter component
$filterId = 'messages-filter';
$filters = [
    'search' => true,
    'status' => [
        'options' => [
            'all' => 'All Messages',
            'unread' => 'Unread',
            'read' => 'Read'
        ]
    ],
    'date_range' => true
];
$sortOptions = [
    'date_desc' => 'Newest First',
    'date_asc' => 'Oldest First',
    'name_asc' => 'Name (A-Z)'
];
$defaultColumns = ['date', 'name', 'email', 'subject', 'status', 'actions'];
?>

<div class="w-full">
    <!-- Header -->
    <div class="bg-gradient-to-r from-red-600 to-pink-600 rounded-xl shadow-xl p-8 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">
                    <i class="fas fa-envelope mr-3"></i>
                    Contact Messages
                </h1>
                <p class="text-red-100 text-lg">Manage customer inquiries and messages</p>
            </div>
            <div class="bg-white/20 rounded-full px-6 py-3 backdrop-blur-sm">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-inbox"></i>
                    <span class="font-semibold"><?= count($messages) ?> Messages</span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($successMessage): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2 text-xl"></i>
            <span class="font-semibold"><?= escape($successMessage) ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($message)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2 text-xl"></i>
            <span class="font-semibold"><?= escape($message) ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Advanced Filters -->
    <?php include __DIR__ . '/includes/advanced-filters.php'; ?>
    
    <!-- Stats Bar -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-6">
                <div>
                    <span class="text-sm text-gray-600">Total Messages:</span>
                    <span class="ml-2 font-bold text-gray-900"><?= count($messages) ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Messages Table -->
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
                    
                    <?php if (in_array('subject', $selectedColumns) || empty($_GET['columns'])): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="subject">Subject</th>
                    <?php endif; ?>
                    
                    <?php if (in_array('message', $selectedColumns)): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="message">Message</th>
                    <?php endif; ?>
                    
                    <?php if (in_array('status', $selectedColumns) || empty($_GET['columns'])): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="status">Status</th>
                    <?php endif; ?>
                    
                    <?php if (in_array('actions', $selectedColumns) || empty($_GET['columns'])): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" data-column="actions">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($messages)): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">No messages found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                    <tr class="<?= !$msg['is_read'] ? 'bg-blue-50' : '' ?>">
                        <?php if (in_array('date', $selectedColumns) || empty($_GET['columns'])): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-column="date">
                            <?= date('M d, Y H:i', strtotime($msg['created_at'])) ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('name', $selectedColumns) || empty($_GET['columns'])): ?>
                        <td class="px-6 py-4" data-column="name">
                            <div class="text-sm font-medium"><?= escape($msg['name']) ?></div>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('email', $selectedColumns) || empty($_GET['columns'])): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm" data-column="email">
                            <a href="mailto:<?= escape($msg['email']) ?>" class="text-blue-600 hover:underline">
                                <?= escape($msg['email']) ?>
                            </a>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('phone', $selectedColumns)): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-column="phone">
                            <?= escape($msg['phone'] ?? '-') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('subject', $selectedColumns) || empty($_GET['columns'])): ?>
                        <td class="px-6 py-4 text-sm text-gray-900" data-column="subject">
                            <?= escape($msg['subject'] ?? 'No Subject') ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('message', $selectedColumns)): ?>
                        <td class="px-6 py-4 text-sm text-gray-500" data-column="message">
                            <div class="max-w-xs truncate" title="<?= escape($msg['message'] ?? '') ?>">
                                <?= escape(substr($msg['message'] ?? '', 0, 50)) ?>...
                            </div>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('status', $selectedColumns) || empty($_GET['columns'])): ?>
                        <td class="px-6 py-4 whitespace-nowrap" data-column="status">
                            <?php if (!$msg['is_read']): ?>
                                <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">New</span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-800">Read</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        
                        <?php if (in_array('actions', $selectedColumns) || empty($_GET['columns'])): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2" data-column="actions">
                            <button onclick="showMessage(<?= htmlspecialchars(json_encode($msg), ENT_QUOTES) ?>)" 
                                    class="text-blue-600 hover:text-blue-900" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if (!$msg['is_read']): ?>
                                <a href="?mark_read=<?= $msg['id'] ?>" class="text-green-600 hover:text-green-900" title="Mark Read">
                                    <i class="fas fa-check"></i>
                                </a>
                            <?php endif; ?>
                            <a href="?delete=<?= $msg['id'] ?>" 
                               onclick="return confirm('Are you sure?')" 
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

<div id="messageModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Message Details</h3>
            <button onclick="closeMessage()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>
        <div id="messageContent"></div>
        <button onclick="closeMessage()" class="mt-4 btn-primary">
            Close
        </button>
    </div>
</div>

<script>
function showMessage(msg) {
    const content = document.getElementById('messageContent');
    content.innerHTML = `
        <div class="space-y-3">
            <div><strong>From:</strong> ${msg.name} &lt;${msg.email}&gt;</div>
            ${msg.phone ? `<div><strong>Phone:</strong> ${msg.phone}</div>` : ''}
            <div><strong>Subject:</strong> ${msg.subject || 'No Subject'}</div>
            <div><strong>Date:</strong> ${new Date(msg.created_at).toLocaleString()}</div>
            <div class="border-t pt-3 mt-3">
                <strong>Message:</strong>
                <p class="mt-2 text-gray-700 whitespace-pre-wrap">${msg.message}</p>
            </div>
        </div>
    `;
    document.getElementById('messageModal').classList.remove('hidden');
}

function closeMessage() {
    document.getElementById('messageModal').classList.add('hidden');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
