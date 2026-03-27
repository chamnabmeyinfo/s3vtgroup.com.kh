<?php
require_once __DIR__ . '/../../bootstrap/app.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$sessionId = $_GET['session_id'] ?? '';

if (empty($sessionId)) {
    echo json_encode(['error' => 'Session ID required']);
    exit;
}

// Read progress from session file
$progressFile = __DIR__ . '/../../storage/cache/import_progress_' . $sessionId . '.json';

if (!file_exists($progressFile)) {
    echo json_encode([
        'status' => 'not_started',
        'message' => 'Import not started yet'
    ]);
    exit;
}

$progress = json_decode(file_get_contents($progressFile), true);

// Check if import is complete
if (isset($progress['status']) && $progress['status'] === 'completed') {
    // Clean up progress file after 5 minutes
    if (isset($progress['completed_at']) && (time() - $progress['completed_at']) > 300) {
        @unlink($progressFile);
    }
}

echo json_encode($progress);
