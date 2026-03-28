<?php
/**
 * AJAX endpoint: add a new dynamic field definition to an item.
 *
 * POST params:
 *   item_code            — 10-hex item public_code
 *   label                — field label (required)
 *   field_type           — text|textarea|number|date|checkbox|photo|document|signature
 *   required             — 0|1
 *   allow_multiple       — 0|1 (applicable to photo/document/signature)
 *   instructions         — optional instructions text
 *   require_printed_name — 0|1 (applicable to signature)
 *
 * Returns JSON: { success: bool, field_id?: int, error?: string }
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/client_helper.php';
require_once __DIR__ . '/../lib/form_helpers.php';

header('Content-Type: application/json');

// Only admins may add field definitions
if (!ClientHelper::isActiveUserAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$item_code           = FormHelper::getPost('item_code');
$label               = FormHelper::getPost('label');
$field_type          = FormHelper::getPost('field_type');
$required            = isset($_POST['required'])            ? 1 : 0;
$allow_multiple      = isset($_POST['allow_multiple'])      ? 1 : 0;
$instructions        = FormHelper::getPost('instructions');
$require_printed_name = isset($_POST['require_printed_name']) ? 1 : 0;

// Validation
if (!FormHelper::isValidHex10($item_code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid item code']);
    exit;
}

$item = DatabaseHelper::queryOne("SELECT public_code FROM items WHERE public_code = ?", [$item_code]);
if (!$item) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Item not found']);
    exit;
}

if (!FormHelper::isRequired($label)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Label is required']);
    exit;
}

$valid_types = ['text','textarea','number','date','checkbox','photo','document','signature'];
if (!in_array($field_type, $valid_types)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid field type']);
    exit;
}

// Determine next sort order (place new field at the end)
$max = DatabaseHelper::queryOne(
    "SELECT MAX(sort_order) AS max_sort FROM item_fields WHERE item_public_code = ?",
    [$item_code]
);
$sort_order = ($max && $max['max_sort'] !== null) ? (int)$max['max_sort'] + 1 : 1;

$field_key = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($label)));

// Ensure the key is unique for this item (e.g. 'User ID' and 'User-ID' both normalise to 'user_id')
$base_key = $field_key;
$suffix   = 2;
while (DatabaseHelper::queryOne(
    "SELECT id FROM item_fields WHERE item_public_code = ? AND field_key = ?",
    [$item_code, $field_key]
)) {
    $field_key = $base_key . '_' . $suffix++;
}

$affected = DatabaseHelper::execute(
    "INSERT INTO item_fields
         (item_public_code, field_key, label, field_type, required, sort_order, allow_multiple, instructions, require_printed_name)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
    [$item_code, $field_key, $label, $field_type, $required, $sort_order, $allow_multiple, $instructions, $require_printed_name]
);

if ($affected <= 0) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => DatabaseHelper::getLastError() ?: 'Insert failed']);
    exit;
}

$field_id = (int)DatabaseHelper::getLastInsertId();
echo json_encode(['success' => true, 'field_id' => $field_id]);
