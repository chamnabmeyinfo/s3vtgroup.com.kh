<?php
/**
 * Backup Management
 */
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Core\Backup\BackupService;

$pageTitle = 'Backup Management';
include __DIR__ . '/includes/header.php';

$backupService = new BackupService();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_backup') {
        try {
            $backupFile = $backupService->backupDatabase();
            $message = 'Backup created successfully! File: ' . basename($backupFile);
        } catch (\Exception $e) {
            $error = 'Error creating backup: ' . $e->getMessage();
        }
    }
}

$backups = $backupService->listBackups();
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Backup Management</h1>
        <form method="POST" class="inline">
            <input type="hidden" name="action" value="create_backup">
            <button type="submit" class="btn-primary">
                <i class="fas fa-database mr-2"></i> Create Backup
            </button>
        </form>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?= escape($message) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= escape($error) ?>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Backup File</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Size</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($backups)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">No backups found. Create your first backup!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($backups as $backup): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <?= escape($backup['file']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?= number_format($backup['size'] / 1024, 2) ?> KB
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?= escape($backup['date']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                            <a href="<?= url('admin/backup-download.php?file=' . urlencode($backup['file'])) ?>" 
                               class="text-blue-600 hover:underline">Download</a>
                            <button onclick="confirmRestore('<?= escape($backup['file']) ?>')" 
                                    class="text-green-600 hover:underline">Restore</button>
                            <button onclick="confirmDelete('<?= escape($backup['file']) ?>')" 
                                    class="text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="font-bold mb-2">Backup Information</h3>
        <ul class="text-sm text-gray-700 space-y-1">
            <li>• Backups are automatically compressed to save space</li>
            <li>• Old backups (older than 30 days) are automatically deleted</li>
            <li>• Backups include all database tables and data</li>
            <li>• Store backups in a secure location for disaster recovery</li>
        </ul>
    </div>
</div>

<script>
function confirmRestore(filename) {
    if (confirm('WARNING: This will restore the database from backup. All current data will be replaced. Continue?')) {
        window.location.href = 'backup-restore.php?file=' + encodeURIComponent(filename);
    }
}

function confirmDelete(filename) {
    if (confirm('Delete this backup file?')) {
        window.location.href = 'backup-delete.php?file=' + encodeURIComponent(filename);
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

