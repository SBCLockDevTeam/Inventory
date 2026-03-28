<?php
/**
 * AJAX endpoint: move a field definition up or down in sort order.
 *
 * POST params:
 *   field_id  — int, item_fields.id to move
 *   item_code — 10-hex item public_code (ownership verification)
 *   direction — 'up' or 'down'
 *
 * Returns JSON: { success: bool, error?: string }
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/client_helper.php';
require_once __DIR__ . '/../lib/form_helpers.php';

header('Content-Type: application/json');

// Only admins may reorder field definitions
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

$field_id  = isset($_POST['field_id']) ? (int)$_POST['field_id'] : 0;
$item_code = FormHelper::getPost('item_code');
$direction = FormHelper::getPost('direction');

if ($field_id <= 0 || !FormHelper::isValidHex10($item_code) || !in_array($direction, ['up', 'down'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// Verify field belongs to the stated item
$field = DatabaseHelper::queryOne(
    "SELECT id FROM item_fields WHERE id = ? AND item_public_code = ?",
    [$field_id, $item_code]
);
if (!$field) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Field not found']);
    exit;
}

// Load all fields ordered by sort_order to determine position robustly
$all_fields = DatabaseHelper::queryAll(
    "SELECT id, sort_order FROM item_fields WHERE item_public_code = ? ORDER BY sort_order ASC, id ASC",
    [$item_code]
);

// Normalise sort_order values to 1, 2, 3… so there are no gaps before swapping
DatabaseHelper::beginTransaction();
try {
    foreach ($all_fields as $i => $f) {
        DatabaseHelper::execute(
            "UPDATE item_fields SET sort_order = ? WHERE id = ?",
            [$i + 1, $f['id']]
        );
        $all_fields[$i]['sort_order'] = $i + 1;
    }

    // Find current field position
    $pos = null;
    foreach ($all_fields as $i => $f) {
        if ((int)$f['id'] === $field_id) {
            $pos = $i;
            break;
        }
    }

    if ($pos === null) {
        DatabaseHelper::rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Could not locate field position']);
        exit;
    }

    $swap_pos = ($direction === 'up') ? $pos - 1 : $pos + 1;

    if ($swap_pos < 0 || $swap_pos >= count($all_fields)) {
        // Already at the boundary — nothing to do but still a success
        DatabaseHelper::commit();
        echo json_encode(['success' => true]);
        exit;
    }

    // Swap sort_order values between the two adjacent fields
    $a = $all_fields[$pos];
    $b = $all_fields[$swap_pos];

    DatabaseHelper::execute("UPDATE item_fields SET sort_order = ? WHERE id = ?", [$b['sort_order'], $a['id']]);
    DatabaseHelper::execute("UPDATE item_fields SET sort_order = ? WHERE id = ?", [$a['sort_order'], $b['id']]);

    DatabaseHelper::commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    DatabaseHelper::rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Reorder failed: ' . $e->getMessage()]);
}
