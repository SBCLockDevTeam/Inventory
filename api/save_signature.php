<?php
/**
 * AJAX endpoint: save a signature captured on the client canvas.
 *
 * POST params:
 *   signature_data  — base64 data URL (image/png)
 *   field_id        — int, item_field.id
 *   item_code       — 10-hex item public_code
 *   printed_name    — string (optional unless require_printed_name = 1)
 *
 * Returns JSON:
 *   { success: true,  signature_id: int, url: string }
 *   { success: false, error: string }
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/client_helper.php';
require_once __DIR__ . '/../lib/form_helpers.php';
require_once __DIR__ . '/../lib/field_helper.php';

header('Content-Type: application/json');

if (!AuthHelper::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$field_id       = isset($_POST['field_id']) ? (int)$_POST['field_id'] : 0;
$item_code      = FormHelper::getPost('item_code');
$printed_name   = FormHelper::getPost('printed_name');
$signature_data = $_POST['signature_data'] ?? '';  // raw data URL — NOT sanitized through FormHelper to preserve base64

if ($field_id <= 0 || !FormHelper::isValidHex10($item_code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// Validate the data URL starts with the expected prefix
if (!preg_match('/^data:image\/png;base64,/', $signature_data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid signature data']);
    exit;
}

// Verify the field belongs to this item and is a signature field
$field = DatabaseHelper::queryOne(
    "SELECT id, require_printed_name FROM item_fields WHERE id = ? AND item_public_code = ? AND field_type = 'signature'",
    [$field_id, $item_code]
);
if (!$field) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Field not found']);
    exit;
}

if ($field['require_printed_name'] && empty(trim($printed_name))) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Printed name is required for this signature']);
    exit;
}

// Decode and save the PNG
$base64   = preg_replace('/^data:image\/png;base64,/', '', $signature_data);
$img_data = base64_decode($base64);
if ($img_data === false || strlen($img_data) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Could not decode signature image']);
    exit;
}

$upload_dir = SERVER_ROOT . '/uploads/signatures/' . $item_code . '/';
if (!is_dir($upload_dir)) {
    // Use 0775 so the directory is group-writable (www-data:www-data ownership set
    // at deployment). chmod() is called explicitly because mkdir()'s mode is subject
    // to the process umask, which can silently strip the group-write bit.
    if (!mkdir($upload_dir, 0775, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Could not create upload directory']);
        exit;
    }
    chmod($upload_dir, 0775);
}

$filename = bin2hex(random_bytes(8)) . '.png';
$dest     = $upload_dir . $filename;
if (file_put_contents($dest, $img_data) === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save signature image']);
    exit;
}

$url_path    = BASE_PATH . '/uploads/signatures/' . $item_code . '/' . $filename;
$sig_id      = FieldHelper::saveSignature($item_code, $field_id, $url_path, $printed_name);

if ($sig_id <= 0) {
    @unlink($dest);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database save failed']);
    exit;
}

$active_user = ClientHelper::getActiveUser();
FieldHelper::logGeneral('signature_saved', $item_code, $field_id, null, $url_path, null,
    $active_user ? $active_user['name'] : null);

echo json_encode(['success' => true, 'signature_id' => $sig_id, 'url' => $url_path]);
