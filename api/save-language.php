<?php
/**
 * Save Language Preference API
 * Saves user's language preference to session
 */

require_once __DIR__ . '/../bootstrap/app.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (empty($data['language'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Language code required']);
    exit;
}

$language = trim($data['language']);

// Validate language code
$validLanguages = ['en', 'km', 'th', 'vi', 'zh', 'ja', 'fr', 'de', 'es', 'ru', 'ko', 'ar'];
if (!in_array($language, $validLanguages)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid language code']);
    exit;
}

// Save to session
$_SESSION['site_language'] = $language;

// Optionally save to database for logged-in users
if (isset($_SESSION['customer_id'])) {
    try {
        $db = \App\Database\Connection::getInstance();
        $db->query(
            "UPDATE customers SET language = :language WHERE id = :id",
            ['language' => $language, 'id' => $_SESSION['customer_id']]
        );
    } catch (\Exception $e) {
        // Ignore database errors, session is enough
    }
}

echo json_encode([
    'success' => true,
    'language' => $language,
    'message' => 'Language preference saved'
]);
