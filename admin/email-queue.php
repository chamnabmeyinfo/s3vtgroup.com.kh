<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Setting;

$pageTitle = 'Email Queue';
include __DIR__ . '/includes/header.php';

// Get email queue
$emails = db()->fetchAll("SELECT * FROM email_queue ORDER BY created_at DESC LIMIT 100");
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Email Queue</h1>
        <button onclick="processQueue()" class="btn-primary">
            <i class="fas fa-paper-plane mr-2"></i> Process Queue
        </button>
    </div>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">To</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attempts</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($emails)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No emails in queue</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($emails as $email): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?= escape($email['id']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?= escape($email['to_email']) ?></td>
                        <td class="px-6 py-4 text-sm"><?= escape($email['subject']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded <?= 
                                $email['status'] === 'sent' ? 'bg-green-100 text-green-800' : 
                                ($email['status'] === 'failed' ? 'bg-red-100 text-red-800' : 
                                'bg-yellow-100 text-yellow-800') 
                            ?>">
                                <?= ucfirst($email['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?= escape($email['attempts']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?= date('M d, Y H:i', strtotime($email['created_at'])) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button onclick="viewEmail(<?= $email['id'] ?>)" class="text-blue-600 hover:underline">View</button>
                            <?php if ($email['status'] === 'pending'): ?>
                                <button onclick="retryEmail(<?= $email['id'] ?>)" class="ml-2 text-green-600 hover:underline">Retry</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Email View Modal -->
<div id="email-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Email Details</h2>
            <button onclick="closeEmailModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="email-content"></div>
    </div>
</div>

<script>
function viewEmail(id) {
    // In a real implementation, fetch email details via API
    document.getElementById('email-modal').classList.remove('hidden');
    document.getElementById('email-content').innerHTML = '<p>Loading...</p>';
    // Fetch and display email content
}

function closeEmailModal() {
    document.getElementById('email-modal').classList.add('hidden');
}

function processQueue() {
    if (confirm('Process all pending emails in queue?')) {
        // In a real implementation, call API to process queue
        alert('Email processing would be done here. Configure SMTP settings first.');
    }
}

function retryEmail(id) {
    if (confirm('Retry sending this email?')) {
        // In a real implementation, call API to retry
        location.reload();
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

