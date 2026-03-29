<?php
/**
 * AJAX endpoint: set the publicly_viewable flag on a field definition.
 *
 * POST params:
 *   field_id          — int, item_fields.id
 *   item_code         — 10-hex item public_code (ownership verification)
 *   publicly_viewable — 0|1
 *
 * Returns JSON: { success: bool, error?: string }
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/client_helper.php';
require_once __DIR__ . '/../lib/form_helpers.php';

header('Content-Type: application/json');

// Only admins may change field visibility
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

$field_id          = isset($_POST['field_id']) ? (int)$_POST['field_id'] : 0;
$item_code         = FormHelper::getPost('item_code');
$publicly_viewable = isset($_POST['publicly_viewable']) ? 1 : 0;

if ($field_id <= 0 || !FormHelper::isValidHex10($item_code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// Verify the field belongs to the stated item
$field = DatabaseHelper::queryOne(
    "SELECT id FROM item_fields WHERE id = ? AND item_public_code = ?",
    [$field_id, $item_code]
);
if (!$field) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Field not found']);
    exit;
}

DatabaseHelper::execute(
    "UPDATE item_fields SET publicly_viewable = ? WHERE id = ? AND item_public_code = ?",
    [$publicly_viewable, $field_id, $item_code]
);

echo json_encode(['success' => true]);
