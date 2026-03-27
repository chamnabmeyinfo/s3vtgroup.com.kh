<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Core\Backup\BackupService;

$filename = basename($_GET['file'] ?? '');
$backupService = new BackupService();

if (!$filename || !preg_match('/^db_backup_.+\.sql(\.gz)?$/', $filename)) {
    die('Invalid file');
}

$filePath = __DIR__ . '/../storage/backups/' . $filename;

if (!file_exists($filePath)) {
    die('File not found');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit;

