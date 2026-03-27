<?php
/**
 * AJAX endpoint: delete a photo attached to an item field.
 *
 * POST params:
 *   image_id   — int, item_images.id
 *   item_code  — 10-hex public_code (ownership verification)
 *
 * Returns JSON: { success: bool, error?: string }
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/client_helper.php';
require_once __DIR__ . '/../lib/form_helpers.php';
require_once __DIR__ . '/../lib/field_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$image_id  = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
$item_code = FormHelper::getPost('item_code');

if ($image_id <= 0 || !FormHelper::isValidHex10($item_code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// Fetch the record first so we can delete the physical file
$photo = DatabaseHelper::queryOne(
    "SELECT id, file_path FROM item_images WHERE id = ? AND item_public_code = ?",
    [$image_id, $item_code]
);
if (!$photo) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Photo not found']);
    exit;
}

$ok = FieldHelper::deletePhoto($image_id, $item_code);
if (!$ok) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Delete failed']);
    exit;
}

// Remove the physical file — validate path is within the uploads directory
$server_path  = SERVER_ROOT . str_replace(BASE_PATH, '', $photo['file_path']);
$uploads_root = realpath(SERVER_ROOT . '/uploads/');
$real_path    = realpath($server_path);
if ($uploads_root && $real_path && strpos($real_path, $uploads_root . DIRECTORY_SEPARATOR) === 0) {
    @unlink($real_path);
}

$active_user = ClientHelper::getActiveUser();
FieldHelper::logGeneral('photo_deleted', $item_code, null, $photo['file_path'], null, null,
    $active_user ? $active_user['name'] : null);

echo json_encode(['success' => true]);
