<?php
/**
 * api/set_printer.php
 *
 * AJAX endpoint — persists the authenticated user's printer selection in the
 * database so the choice survives across browsers and devices.
 *
 * Request  POST  printer_id (int, required)
 * Response JSON  { "success": true }
 *             or { "success": false, "error": "..." }
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/auth_helper.php';

header('Content-Type: application/json');

// Must be authenticated
if (!AuthHelper::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

// Must be POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$printer_id = isset($_POST['printer_id']) ? (int)$_POST['printer_id'] : 0;

if ($printer_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid printer_id.']);
    exit;
}

// Verify the printer exists and is active
$printer = DatabaseHelper::queryOne(
    "SELECT id FROM printers WHERE id = ? AND is_active = 1",
    [$printer_id]
);
if (!$printer) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Printer not found or inactive.']);
    exit;
}

$auth_user = AuthHelper::getAuthUser();
$user_id   = (int)$auth_user['user_id'];

DatabaseHelper::execute(
    "UPDATE users SET preferred_printer_id = ? WHERE id = ?",
    [$printer_id, $user_id]
);

echo json_encode(['success' => true]);
