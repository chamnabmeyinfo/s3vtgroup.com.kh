<?php
/**
 * Website Under Maintenance Control Panel
 */
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Helpers\UnderConstruction;
use App\Models\Setting;

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'enable') {
        UnderConstruction::enable();
        $message = 'Maintenance mode has been enabled. Only logged-in admin users can access the frontend.';
        $messageType = 'success';
    } elseif ($action === 'disable') {
        UnderConstruction::disable();
        $message = 'Maintenance mode has been disabled. Website is now live for everyone.';
        $messageType = 'success';
    }
}

// Get current status
$configFile = __DIR__ . '/../config/under-construction.php';
$isEnabled = false;
$config = [];

if (file_exists($configFile)) {
    $config = require $configFile;
    $isEnabled = $config['enabled'] ?? false;
}

// Get notification count
$db = db();
$notificationCount = 0;
try {
    $result = $db->fetchOne("SELECT COUNT(*) as total FROM construction_notifications");
    $notificationCount = $result['total'] ?? 0;
} catch (Exception $e) {
    // Table doesn't exist yet
    $notificationCount = 0;
}

$pageTitle = 'Website Maintenance Control';
include __DIR__ . '/includes/header.php';
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Website Maintenance Control</h1>
    </div>
    
    <?php if (!empty($message)): ?>
    <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
        <?= escape($message) ?>
    </div>
    <?php endif; ?>
    
    <!-- Status Card -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">Current Status</h2>
            <span class="px-4 py-2 rounded-full text-sm font-semibold <?= $isEnabled ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                <?= $isEnabled ? '🟡 ENABLED' : '🟢 DISABLED' ?>
            </span>
        </div>
        
        <div class="mb-6">
            <p class="text-gray-600 mb-4">
                <?php if ($isEnabled): ?>
                    <strong>Maintenance mode is ACTIVE.</strong> Only logged-in admin users can access the frontend.
                    Public visitors will see the maintenance page. Admin panel access remains available.
                <?php else: ?>
                    The website is live and accessible to all visitors.
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Toggle Form -->
        <form method="POST" class="flex gap-4">
            <?php if ($isEnabled): ?>
                <button type="submit" 
                        name="action" 
                        value="disable"
                        class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-all">
                    <i class="fas fa-play mr-2"></i>
                    Go Live (Disable Maintenance Mode)
                </button>
            <?php else: ?>
                <button type="submit" 
                        name="action" 
                        value="enable"
                        class="px-6 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg transition-all">
                    <i class="fas fa-tools mr-2"></i>
                    Enable Maintenance Mode
                </button>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Statistics -->
    <div class="grid md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm mb-1">Email Notifications</p>
                    <p class="text-3xl font-bold text-blue-600"><?= number_format($notificationCount) ?></p>
                </div>
                <div class="text-4xl text-blue-400">
                    <i class="fas fa-envelope"></i>
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-2">People waiting for launch</p>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm mb-1">Construction Progress</p>
                    <p class="text-3xl font-bold text-yellow-600"><?= escape($config['progress'] ?? 85) ?>%</p>
                </div>
                <div class="text-4xl text-yellow-400">
                    <i class="fas fa-tools"></i>
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-2">Website completion</p>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm mb-1">Status</p>
                    <p class="text-3xl font-bold <?= $isEnabled ? 'text-yellow-600' : 'text-green-600' ?>">
                        <?= $isEnabled ? 'OFFLINE' : 'ONLINE' ?>
                    </p>
                </div>
                <div class="text-4xl <?= $isEnabled ? 'text-yellow-400' : 'text-green-400' ?>">
                    <i class="fas fa-<?= $isEnabled ? 'pause-circle' : 'check-circle' ?>"></i>
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-2">Current website status</p>
        </div>
    </div>
    
    <!-- Email List -->
    <?php if ($notificationCount > 0): ?>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-bold mb-4">
            <i class="fas fa-list mr-2"></i>
            Email Notification List
        </h3>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Email</th>
                        <th class="px-4 py-2 text-left">Date</th>
                        <th class="px-4 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $notifications = $db->fetchAll(
                            "SELECT * FROM construction_notifications ORDER BY created_at DESC LIMIT 50"
                        );
                    } catch (Exception $e) {
                        $notifications = [];
                    }
                    
                    foreach ($notifications as $notification):
                    ?>
                    <tr class="border-t">
                        <td class="px-4 py-2"><?= escape($notification['email']) ?></td>
                        <td class="px-4 py-2 text-gray-600">
                            <?= date('M d, Y H:i', strtotime($notification['created_at'])) ?>
                        </td>
                        <td class="px-4 py-2">
                            <a href="mailto:<?= escape($notification['email']) ?>" 
                               class="text-blue-600 hover:underline">
                                <i class="fas fa-envelope mr-1"></i> Email
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($notificationCount > 50): ?>
        <p class="mt-4 text-sm text-gray-600">
            Showing last 50 notifications. Total: <?= number_format($notificationCount) ?>
        </p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Instructions -->
    <div class="bg-blue-50 rounded-lg p-6 mt-6">
        <h3 class="text-lg font-bold text-blue-900 mb-2">
            <i class="fas fa-info-circle mr-2"></i>
            How It Works
        </h3>
        <ul class="text-blue-800 space-y-2 list-disc list-inside">
            <li><strong>When enabled:</strong> Only logged-in admin users can access the frontend</li>
            <li>Public visitors will see the "Website Under Maintenance" page</li>
            <li>Admin panel and API endpoints remain fully accessible</li>
            <li>Logged-in admin users can browse the website normally</li>
            <li>Visitors can subscribe to be notified when maintenance is complete</li>
            <li>Email notifications are stored in the database for future reference</li>
        </ul>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

