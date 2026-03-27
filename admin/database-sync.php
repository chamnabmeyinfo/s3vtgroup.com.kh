<?php
/**
 * Database Sync Management
 * Smart database synchronization between local and remote
 */

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/header.php';

use App\Services\DatabaseSyncService;

$syncService = new DatabaseSyncService();
$message = '';
$messageType = '';

// Load deployment config for remote database settings
$configFile = __DIR__ . '/../deploy-config.json';
$config = [];
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
}

$remoteDbConfig = $config['database_remote'] ?? [];

// Handle sync actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'pull':
                // Pull from remote to local
                $options = [
                    'backup_before_pull' => isset($_POST['backup_before_pull']),
                    'merge_strategy' => $_POST['merge_strategy'] ?? 'remote_priority',
                    'tables_to_sync' => !empty($_POST['tables_to_sync']) ? explode(',', $_POST['tables_to_sync']) : null,
                ];
                
                $result = $syncService->pullFromRemote($remoteDbConfig, $options);
                
                if ($result['success']) {
                    $message = $result['message'];
                    $messageType = 'success';
                } else {
                    $message = $result['message'];
                    $messageType = 'error';
                }
                break;
                
            case 'push':
                // Push from local to remote
                $options = [
                    'backup_before_push' => isset($_POST['backup_before_push']),
                    'merge_strategy' => $_POST['merge_strategy'] ?? 'local_priority',
                ];
                
                $result = $syncService->pushToRemote($remoteDbConfig, $options);
                
                if ($result['success']) {
                    $message = $result['message'];
                    $messageType = 'success';
                } else {
                    $message = $result['message'];
                    $messageType = 'error';
                }
                break;
                
            case 'compare':
                // Compare local and remote
                $comparison = $syncService->compareDatabases($remoteDbConfig);
                $message = "Comparison complete. See details below.";
                $messageType = 'info';
                break;
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Get sync status
$syncStatus = $syncService->getSyncStatus($remoteDbConfig);
$syncLog = $syncService->getLog();

?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Database Sync Management
                </h1>
                <div class="badge badge-info">
                    <i class="fas fa-database mr-1"></i>
                    Smart Sync Enabled
                </div>
            </div>

            <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'danger' : 'info') ?> alert-dismissible fade show">
                <?= escape($message) ?>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
            <?php endif; ?>

            <!-- Sync Status Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle mr-2"></i>
                        Sync Status
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-circle bg-success text-white mr-3">
                                    <i class="fas fa-database"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Local Database</div>
                                    <div class="h5 mb-0"><?= $syncStatus['local_tables'] ?> Tables</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-circle bg-info text-white mr-3">
                                    <i class="fas fa-cloud"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Remote Database</div>
                                    <div class="h5 mb-0">s3vgroup.com</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-circle bg-warning text-white mr-3">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Last Sync</div>
                                    <div class="h5 mb-0">
                                        <?= $syncStatus['last_sync'] ? date('M d, Y H:i', strtotime($syncStatus['last_sync'])) : 'Never' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sync Actions -->
            <div class="row">
                <!-- Pull from Remote -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-download mr-2"></i>
                                Pull from Remote (s3vgroup.com)
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                <strong>Priority: Remote Server</strong><br>
                                Downloads the latest database from s3vgroup.com and updates your local database.
                                Use this before making changes to ensure you're working with the latest data.
                            </p>
                            
                            <form method="POST" class="sync-form">
                                <input type="hidden" name="action" value="pull">
                                
                                <div class="form-group">
                                    <label>Merge Strategy</label>
                                    <select name="merge_strategy" class="form-control">
                                        <option value="remote_priority" selected>Remote Priority (Recommended)</option>
                                        <option value="newer_wins">Newer Wins</option>
                                        <option value="manual">Manual Resolution</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        How to handle conflicts between local and remote data
                                    </small>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="backup_before_pull" class="form-check-input" id="backup_pull" checked>
                                    <label class="form-check-label" for="backup_pull">
                                        Create backup before pull
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-download mr-2"></i>
                                    Pull from Remote
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Push to Remote -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-upload mr-2"></i>
                                Push to Remote (s3vgroup.com)
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                <strong>Deploy Local Changes</strong><br>
                                Uploads your local database changes to s3vgroup.com.
                                This will overwrite the remote database with your local data.
                            </p>
                            
                            <form method="POST" class="sync-form">
                                <input type="hidden" name="action" value="push">
                                
                                <div class="form-group">
                                    <label>Merge Strategy</label>
                                    <select name="merge_strategy" class="form-control">
                                        <option value="local_priority" selected>Local Priority (Recommended)</option>
                                        <option value="newer_wins">Newer Wins</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        How to handle conflicts when pushing
                                    </small>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="backup_before_push" class="form-check-input" id="backup_push" checked>
                                    <label class="form-check-label" for="backup_push">
                                        Create remote backup before push
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-upload mr-2"></i>
                                    Push to Remote
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sync Workflow Guide -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb mr-2"></i>
                        Recommended Workflow
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="workflow-step">
                                <div class="step-number bg-success">1</div>
                                <h6>Pull from Remote</h6>
                                <p class="text-muted small">
                                    Always pull the latest data from s3vgroup.com before making changes.
                                    This ensures you're working with the most up-to-date information.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="workflow-step">
                                <div class="step-number bg-info">2</div>
                                <h6>Make Changes Locally</h6>
                                <p class="text-muted small">
                                    Edit products, add data, make updates on your local database.
                                    Test everything locally before deploying.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="workflow-step">
                                <div class="step-number bg-primary">3</div>
                                <h6>Push to Remote</h6>
                                <p class="text-muted small">
                                    Once you're satisfied with your changes, push them to s3vgroup.com.
                                    The deployment system will handle this automatically.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sync History -->
            <?php if (!empty($syncLog)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history mr-2"></i>
                        Sync Log
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_reverse($syncLog) as $logEntry): ?>
                                <tr>
                                    <td><?= escape($logEntry['timestamp']) ?></td>
                                    <td><?= escape($logEntry['message']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.icon-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.workflow-step {
    text-align: center;
    padding: 20px;
}

.step-number {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    font-weight: bold;
    margin: 0 auto 15px;
}

.sync-form {
    margin-top: 20px;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

