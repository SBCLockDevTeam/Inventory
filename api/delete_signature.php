<?php
/**
 * AJAX endpoint: delete a saved signature.
 *
 * POST params:
 *   sig_id     — int, item_signatures.id
 *   item_code  — 10-hex public_code (ownership check)
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

$sig_id    = isset($_POST['sig_id']) ? (int)$_POST['sig_id'] : 0;
$item_code = FormHelper::getPost('item_code');

if ($sig_id <= 0 || !FormHelper::isValidHex10($item_code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$sig = DatabaseHelper::queryOne(
    "SELECT id, signature_image_path FROM item_signatures WHERE id = ? AND item_public_code = ?",
    [$sig_id, $item_code]
);
if (!$sig) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Signature not found']);
    exit;
}

$ok = FieldHelper::deleteSignature($sig_id, $item_code);
if (!$ok) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Delete failed']);
    exit;
}

// Remove physical file — validate path is within the uploads directory
$server_path  = SERVER_ROOT . str_replace(BASE_PATH, '', $sig['signature_image_path']);
$uploads_root = realpath(SERVER_ROOT . '/uploads/');
$real_path    = realpath($server_path);
if ($uploads_root && $real_path && strpos($real_path, $uploads_root . DIRECTORY_SEPARATOR) === 0) {
    @unlink($real_path);
}

$active_user = ClientHelper::getActiveUser();
FieldHelper::logGeneral('signature_deleted', $item_code, null, $sig['signature_image_path'], null, null,
    $active_user ? $active_user['name'] : null);

echo json_encode(['success' => true]);
