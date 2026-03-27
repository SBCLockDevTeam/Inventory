<?php
/**
 * AJAX endpoint: delete a single field definition from an item.
 *
 * Why: Field deletion must be done via POST to prevent CSRF issues with
 *      simple anchor-based requests. Cascading deletes on item_fields
 *      automatically remove field values, images, documents, and signatures
 *      for that field (as defined in the DB schema).
 *
 * POST params:
 *   field_id (int) — ID of the item_field row to delete
 *   item_id  (string) — 10-hex public_code of the owning item (for ownership verification)
 *
 * Returns JSON: { success: bool, error?: string }
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/client_helper.php';
require_once __DIR__ . '/../../lib/form_helpers.php';

header('Content-Type: application/json');

// Only admins may delete field definitions
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

$field_id = isset($_POST['field_id']) ? (int)$_POST['field_id'] : 0;
$item_id  = FormHelper::getPost('item_id');

if ($field_id <= 0 || !FormHelper::isValidHex10($item_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// Verify the field belongs to the stated item (prevents cross-item deletion)
$field = DatabaseHelper::queryOne(
    "SELECT id FROM item_fields WHERE id = ? AND item_public_code = ?",
    [$field_id, $item_id]
);

if (!$field) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Field not found']);
    exit;
}

$affected = DatabaseHelper::execute(
    "DELETE FROM item_fields WHERE id = ?",
    [$field_id]
);

if ($affected > 0) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => DatabaseHelper::getLastError() ?: 'Delete failed']);
}
