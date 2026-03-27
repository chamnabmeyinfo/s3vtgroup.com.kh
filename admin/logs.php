<?php
/**
 * System Logs Viewer
 */
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Services\Logger;

$pageTitle = 'System Logs';
include __DIR__ . '/includes/header.php';

$logger = new Logger();
$date = $_GET['date'] ?? date('Y-m-d');
$level = $_GET['level'] ?? null;
$logs = $logger->getLogs($date, $level, 500);
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">System Logs</h1>
        <div class="flex gap-2">
            <input type="date" value="<?= escape($date) ?>" 
                   onchange="window.location.href='?date=' + this.value + '<?= $level ? '&level=' . $level : '' ?>'"
                   class="px-4 py-2 border rounded-lg">
            <select onchange="window.location.href='?date=<?= escape($date) ?>&level=' + this.value" class="px-4 py-2 border rounded-lg">
                <option value="">All Levels</option>
                <option value="debug" <?= $level === 'debug' ? 'selected' : '' ?>>Debug</option>
                <option value="info" <?= $level === 'info' ? 'selected' : '' ?>>Info</option>
                <option value="warning" <?= $level === 'warning' ? 'selected' : '' ?>>Warning</option>
                <option value="error" <?= $level === 'error' ? 'selected' : '' ?>>Error</option>
                <option value="critical" <?= $level === 'critical' ? 'selected' : '' ?>>Critical</option>
            </select>
            <button onclick="clearLogs()" class="btn-secondary">
                <i class="fas fa-trash mr-2"></i> Clear Logs
            </button>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Timestamp</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Context</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No logs found for this date</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr class="<?= 
                            $log['level'] === 'ERROR' || $log['level'] === 'CRITICAL' ? 'bg-red-50' : 
                            ($log['level'] === 'WARNING' ? 'bg-yellow-50' : '')
                        ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm"><?= escape($log['timestamp']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded <?= 
                                    $log['level'] === 'ERROR' || $log['level'] === 'CRITICAL' ? 'bg-red-100 text-red-800' :
                                    ($log['level'] === 'WARNING' ? 'bg-yellow-100 text-yellow-800' :
                                    ($log['level'] === 'INFO' ? 'bg-blue-100 text-blue-800' :
                                    'bg-gray-100 text-gray-800'))
                                ?>">
                                    <?= escape($log['level']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm"><?= escape($log['message']) ?></td>
                            <td class="px-6 py-4 text-sm">
                                <?php if (!empty($log['context'])): ?>
                                    <button onclick="showContext(<?= htmlspecialchars(json_encode($log['context'])) ?>)" 
                                            class="text-blue-600 hover:underline">
                                        View Context
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm"><?= escape($log['ip'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Context Modal -->
<div id="context-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Log Context</h2>
            <button onclick="closeContext()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <pre id="context-content" class="bg-gray-100 p-4 rounded text-sm overflow-x-auto"></pre>
    </div>
</div>

<script>
function showContext(context) {
    document.getElementById('context-content').textContent = JSON.stringify(context, null, 2);
    document.getElementById('context-modal').classList.remove('hidden');
}

function closeContext() {
    document.getElementById('context-modal').classList.add('hidden');
}

function clearLogs() {
    if (confirm('Clear all logs for this date? This cannot be undone.')) {
        window.location.href = 'logs-clear.php?date=<?= escape($date) ?>';
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

