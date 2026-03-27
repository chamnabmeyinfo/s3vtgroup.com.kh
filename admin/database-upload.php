<?php
/**
 * Database SQL Upload to cPanel
 * Admin interface for uploading database backups to cPanel
 */
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Core\Backup\BackupService;

$pageTitle = 'Database Upload & Import';
include __DIR__ . '/includes/header.php';

$message = '';
$messageType = '';
$backupService = new BackupService();

// Handle import request (direct import to database)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_database'])) {
    try {
        // Load database config
        $dbConfig = require __DIR__ . '/../config/database.php';
        $dbHost = $dbConfig['host'] ?? 'localhost';
        $dbName = $dbConfig['dbname'] ?? $dbConfig['database'] ?? '';
        $dbUser = $dbConfig['username'] ?? '';
        $dbPass = $dbConfig['password'] ?? '';
        
        if (empty($dbName) || empty($dbUser)) {
            throw new Exception('Database credentials incomplete');
        }
        
        // Create backup before import
        $backupBefore = null;
        if (isset($_POST['backup_before']) && $_POST['backup_before'] === '1') {
            $backupBefore = $backupService->backupDatabase();
        }
        
        // Create fresh export
        $sqlFile = $backupService->backupDatabase();
        if (!$sqlFile || !file_exists($sqlFile)) {
            throw new Exception('Failed to create database export');
        }
        
        // Connect to database
        $pdo = new PDO(
            "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
            $dbUser,
            $dbPass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        
        // Read and execute SQL
        $sql = file_get_contents($sqlFile);
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
        $pdo->exec("SET AUTOCOMMIT = 0");
        $pdo->exec("START TRANSACTION");
        
        // Split and execute statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        $executed = 0;
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    $executed++;
                } catch (PDOException $e) {
                    // Ignore errors for DROP TABLE IF EXISTS on non-existent tables
                    if (strpos($e->getMessage(), "Unknown table") === false) {
                        throw $e;
                    }
                }
            }
        }
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        $pdo->exec("COMMIT");
        
        $tableCount = $pdo->query("SHOW TABLES")->rowCount();
        
        // Clean up
        if (file_exists($sqlFile)) {
            unlink($sqlFile);
        }
        
        $message = "Database imported successfully! {$executed} statements executed, {$tableCount} tables in database.";
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = 'Import error: ' . $e->getMessage();
        $messageType = 'error';
        
        // Try to rollback
        if (isset($pdo)) {
            try {
                $pdo->exec("ROLLBACK");
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            } catch (Exception $rollbackError) {
                // Ignore
            }
        }
    }
}

// Handle upload request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_database'])) {
    try {
        // Load deployment config
        $configFile = __DIR__ . '/../deploy-config.json';
        if (!file_exists($configFile)) {
            throw new Exception('deploy-config.json not found');
        }
        
        $config = json_decode(file_get_contents($configFile), true);
        if (!$config) {
            throw new Exception('Invalid deploy-config.json format');
        }
        
        $ftpConfig = $config['ftp'] ?? [];
        if (empty($ftpConfig['host']) || empty($ftpConfig['username'])) {
            throw new Exception('FTP configuration incomplete');
        }
        
        // Create backup first
        $backupFile = $backupService->backupDatabase();
        if (!$backupFile || !file_exists($backupFile)) {
            throw new Exception('Failed to create database backup');
        }
        
        // Upload settings
        $compress = isset($_POST['compress']) && $_POST['compress'] === '1';
        $keepLocal = isset($_POST['keep_local']) && $_POST['keep_local'] === '1';
        
        $fileToUpload = $backupFile;
        
        // Compress if requested
        if ($compress) {
            $compressedFile = $backupFile . '.gz';
            $fp_in = fopen($backupFile, 'rb');
            $fp_out = gzopen($compressedFile, 'wb9');
            
            if ($fp_in && $fp_out) {
                while (!feof($fp_in)) {
                    gzwrite($fp_out, fread($fp_in, 8192));
                }
                fclose($fp_in);
                gzclose($fp_out);
                $fileToUpload = $compressedFile;
            }
        }
        
        // Connect to FTP
        $ftp = @ftp_connect($ftpConfig['host'], $ftpConfig['port'] ?? 21);
        if (!$ftp) {
            throw new Exception('Failed to connect to FTP server');
        }
        
        if (!@ftp_login($ftp, $ftpConfig['username'], $ftpConfig['password'] ?? '')) {
            ftp_close($ftp);
            throw new Exception('FTP login failed');
        }
        
        ftp_pasv($ftp, true);
        
        // Set remote path
        $remotePath = rtrim($ftpConfig['remote_path'] ?? '/public_html', '/');
        $dbRemotePath = $remotePath . '/backups';
        
        // Create directory if needed
        @ftp_mkdir($ftp, $dbRemotePath);
        
        // Upload file
        $remoteFile = $dbRemotePath . '/' . basename($fileToUpload);
        $uploadSuccess = @ftp_put($ftp, $remoteFile, $fileToUpload, FTP_BINARY);
        
        ftp_close($ftp);
        
        if (!$uploadSuccess) {
            throw new Exception('Failed to upload file to FTP server');
        }
        
        // Clean up if not keeping local copy
        if (!$keepLocal) {
            if ($compress && file_exists($compressedFile)) {
                unlink($compressedFile);
            }
            if (file_exists($backupFile)) {
                unlink($backupFile);
            }
        }
        
        $message = 'Database backup uploaded successfully to cPanel!';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get recent backups
$backups = $backupService->listBackups();
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Database Upload & Import</h1>
            <p class="text-gray-600 mt-1">Upload SQL to cPanel or import directly to database (overwrites existing)</p>
        </div>
    </div>
    
    <?php if (!empty($message)): ?>
    <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300' ?>">
        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> mr-2"></i>
        <?= escape($message) ?>
    </div>
    <?php endif; ?>
    
    <!-- Import Form (Direct to Database) -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">
            <i class="fas fa-database mr-2 text-green-600"></i>
            Import Database Directly (Overwrites Existing)
        </h2>
        
        <form method="POST" onsubmit="return confirm('⚠️ WARNING: This will OVERWRITE your entire database! All existing data will be replaced. Are you absolutely sure?');" class="space-y-4">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-sm text-red-800 font-semibold mb-2">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    ⚠️ WARNING: This will completely overwrite your database!
                </p>
                <p class="text-sm text-red-700">
                    This action will drop all existing tables and import fresh data. Make sure you have a backup!
                </p>
            </div>
            
            <div class="space-y-3">
                <label class="flex items-center">
                    <input type="checkbox" name="backup_before" value="1" checked class="mr-2">
                    <span>Create backup before importing (recommended)</span>
                </label>
            </div>
            
            <button type="submit" name="import_database" class="btn-primary bg-green-600 hover:bg-green-700">
                <i class="fas fa-database mr-2"></i> Import Database (Overwrite)
            </button>
        </form>
    </div>
    
    <!-- Upload Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">
            <i class="fas fa-cloud-upload-alt mr-2 text-blue-600"></i>
            Upload SQL File to cPanel (Manual Import)
        </h2>
        
        <form method="POST" class="space-y-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    This will create a new database backup and upload it to your cPanel server via FTP.
                    The file will be uploaded to: <code>/backups/</code> directory on your server.
                    You can then import it manually via phpMyAdmin.
                </p>
            </div>
            
            <div class="space-y-3">
                <label class="flex items-center">
                    <input type="checkbox" name="compress" value="1" checked class="mr-2">
                    <span>Compress SQL file (saves space, creates .gz file)</span>
                </label>
                
                <label class="flex items-center">
                    <input type="checkbox" name="keep_local" value="1" checked class="mr-2">
                    <span>Keep local copy after upload</span>
                </label>
            </div>
            
            <button type="submit" name="upload_database" class="btn-primary">
                <i class="fas fa-upload mr-2"></i> Create Backup & Upload to cPanel
            </button>
        </form>
    </div>
    
    <!-- Recent Backups -->
    <?php if (!empty($backups)): ?>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">
            <i class="fas fa-database mr-2 text-blue-600"></i>
            Recent Backups
        </h2>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">File</th>
                        <th class="px-4 py-3 text-left">Size</th>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($backups, 0, 10) as $backup): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <i class="fas fa-file-archive mr-2 text-blue-600"></i>
                            <?= escape($backup['name']) ?>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <?= escape($backup['size']) ?>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <?= escape($backup['date']) ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="<?= url('admin/backup.php?download=' . urlencode($backup['name'])) ?>" 
                               class="text-blue-600 hover:text-blue-800 mr-3">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Instructions -->
    <div class="bg-blue-50 rounded-lg p-6 mt-6">
        <h3 class="text-lg font-bold text-blue-900 mb-2">
            <i class="fas fa-info-circle mr-2"></i>
            How to Import SQL in cPanel
        </h3>
        <ol class="text-blue-800 space-y-2 list-decimal list-inside">
            <li>Log in to your cPanel account</li>
            <li>Open <strong>phpMyAdmin</strong> from the Databases section</li>
            <li>Select your database from the left sidebar</li>
            <li>Click the <strong>"Import"</strong> tab at the top</li>
            <li>Click <strong>"Choose File"</strong> and select the uploaded SQL file from <code>/backups/</code> directory</li>
            <li>Click <strong>"Go"</strong> to import the database</li>
        </ol>
        <p class="text-blue-800 mt-4">
            <strong>Note:</strong> If the file is compressed (.gz), you may need to extract it first or use phpMyAdmin's import feature which can handle .gz files.
        </p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

