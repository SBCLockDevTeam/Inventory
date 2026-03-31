<?php
/**
 * AJAX endpoint: upload a photo for a specific item field.
 *
 * Why: Photos are uploaded independently of the main form save so the
 *      user gets immediate feedback and does not lose unsaved form data.
 *
 * POST (multipart/form-data):
 *   photo      — uploaded image file
 *   field_id   — int, item_field.id
 *   item_code  — 10-hex item public_code
 *
 * Returns JSON:
 *   { success: true,  image_id: int, url: string }
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

$field_id  = isset($_POST['field_id'])  ? (int)$_POST['field_id']  : 0;
$item_code = FormHelper::getPost('item_code');

if ($field_id <= 0 || !FormHelper::isValidHex10($item_code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// Verify the field belongs to this item
// Note: field_id in item_fields is BIGINT UNSIGNED; cast to int is safe for lookup
$field = DatabaseHelper::queryOne(
    "SELECT id, allow_multiple FROM item_fields WHERE id = ? AND item_public_code = ? AND field_type = 'photo'",
    [$field_id, $item_code]
);
if (!$field) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Field not found']);
    exit;
}

// If single-upload, reject if a photo already exists for this field
if (!$field['allow_multiple']) {
    $existing_count = DatabaseHelper::queryCount(
        "SELECT COUNT(*) AS count FROM item_images WHERE item_public_code = ? AND field_id = ?",
        [$item_code, $field_id]
    );
    if ($existing_count > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'A photo already exists for this field. Delete it first.']);
        exit;
    }
}

// Check file was actually uploaded
if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $err_code = $_FILES['photo']['error'] ?? -1;
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded (error ' . $err_code . ')']);
    exit;
}

$file = $_FILES['photo'];

// Allow only image MIME types — validated against actual file content, not client header
$allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo        = finfo_open(FILEINFO_MIME_TYPE);
$mime         = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed_mime)) {
    http_response_code(415);
    echo json_encode(['success' => false, 'error' => 'Only image files are allowed']);
    exit;
}

// Max size: 10 MB
if ($file['size'] > 10 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['success' => false, 'error' => 'File too large (max 10 MB)']);
    exit;
}

// Build the upload directory on the server
// SERVER_ROOT has no trailing slash; append the full sub-path
$upload_server_dir = SERVER_ROOT . '/uploads/photos/' . $item_code . '/';
if (!is_dir($upload_server_dir)) {
    // Use 0775 so the directory is group-writable (www-data:www-data ownership set
    // at deployment). chmod() is called explicitly because mkdir()'s mode is subject
    // to the process umask, which can silently strip the group-write bit.
    if (!mkdir($upload_server_dir, 0775, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Could not create upload directory']);
        exit;
    }
    chmod($upload_server_dir, 0775);
}

// Derive extension from the validated MIME type (never trust client filename)
$mime_to_ext = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
$ext      = $mime_to_ext[$mime] ?? 'jpg';
$filename = bin2hex(random_bytes(8)) . '.' . $ext;
$dest     = $upload_server_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    exit;
}

// Store the URL-relative path (so the web server can serve it)
// BASE_PATH has no trailing slash, e.g. /qr
$url_path = BASE_PATH . '/uploads/photos/' . $item_code . '/' . $filename;

$image_id = FieldHelper::savePhoto($item_code, $field_id, $url_path);

if ($image_id <= 0) {
    // Attempt to clean up the orphaned file
    @unlink($dest);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database save failed']);
    exit;
}

// Log the upload
$active_user = ClientHelper::getActiveUser();
FieldHelper::logGeneral(
    'photo_uploaded',
    $item_code,
    $field_id,
    null,
    $url_path,
    null,
    $active_user ? $active_user['name'] : null
);

echo json_encode(['success' => true, 'image_id' => $image_id, 'url' => $url_path]);
