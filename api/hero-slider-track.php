<?php
/**
 * Hero Slider Analytics Tracking
 */

require_once __DIR__ . '/../bootstrap/app.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$slideId = (int)($_GET['slide_id'] ?? 0);

if ($slideId > 0) {
    try {
        // Check if views column exists
        try {
            db()->fetchOne("SELECT views FROM hero_slides LIMIT 1");
            // Column exists, update it
            db()->query("UPDATE hero_slides SET views = COALESCE(views, 0) + 1 WHERE id = :id", ['id' => $slideId]);
        } catch (Exception $e) {
            // Column doesn't exist yet, ignore
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid slide ID']);
}

