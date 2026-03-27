<?php
/**
 * AJAX endpoint: upload a document for a specific item field.
 *
 * POST (multipart/form-data):
 *   document   — uploaded file
 *   field_id   — int, item_field.id
 *   item_code  — 10-hex item public_code
 *
 * Returns JSON:
 *   { success: true,  doc_id: int, url: string, filename: string, mime: string }
 *   { success: false, error: string }
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

$field_id  = isset($_POST['field_id'])  ? (int)$_POST['field_id']  : 0;
$item_code = FormHelper::getPost('item_code');

if ($field_id <= 0 || !FormHelper::isValidHex10($item_code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// Verify the field exists and is a document field for this item
$field = DatabaseHelper::queryOne(
    "SELECT id FROM item_fields WHERE id = ? AND item_public_code = ? AND field_type = 'document'",
    [$field_id, $item_code]
);
if (!$field) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Field not found']);
    exit;
}

if (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    $err_code = $_FILES['document']['error'] ?? -1;
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded (error ' . $err_code . ')']);
    exit;
}

$file = $_FILES['document'];

// Max 50 MB for documents
if ($file['size'] > 50 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['success' => false, 'error' => 'File too large (max 50 MB)']);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

// Build upload directory
$upload_server_dir = SERVER_ROOT . '/uploads/documents/' . $item_code . '/';
if (!is_dir($upload_server_dir)) {
    mkdir($upload_server_dir, 0755, true);
}

$original_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$safe_ext     = preg_replace('/[^a-z0-9]/', '', $original_ext);
$filename     = bin2hex(random_bytes(8)) . ($safe_ext ? '.' . $safe_ext : '');
$dest         = $upload_server_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    exit;
}

$url_path         = BASE_PATH . '/uploads/documents/' . $item_code . '/' . $filename;
$original_filename = basename($file['name']);

$doc_id = FieldHelper::saveDocument($item_code, $field_id, $url_path, $original_filename, $mime);

if ($doc_id <= 0) {
    @unlink($dest);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database save failed']);
    exit;
}

$active_user = ClientHelper::getActiveUser();
FieldHelper::logGeneral('document_uploaded', $item_code, $field_id, null, $url_path, null,
    $active_user ? $active_user['name'] : null);

echo json_encode([
    'success'  => true,
    'doc_id'   => $doc_id,
    'url'      => $url_path,
    'filename' => htmlspecialchars($original_filename),
    'mime'     => $mime
]);
