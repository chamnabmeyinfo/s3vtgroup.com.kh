<?php
/**
 * API: Get all images for browser
 */
require_once __DIR__ . '/../../bootstrap/app.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$uploadDir = __DIR__ . '/../../storage/uploads/';
$images = [];

if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_file($uploadDir . $file)) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                $images[] = [
                    'filename' => $file,
                    'url' => asset('storage/uploads/' . $file),
                    'size' => filesize($uploadDir . $file),
                    'date' => filemtime($uploadDir . $file)
                ];
            }
        }
    }
    
    // Sort by date (newest first)
    usort($images, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

echo json_encode(['success' => true, 'images' => $images]);

