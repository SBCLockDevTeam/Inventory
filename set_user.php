<?php
/**
 * AJAX endpoint: set the active client in the session (admin users only).
 * Called by the client selector dropdown in the header.
 *
 * POST params:
 *   client_id (int) — switch to a different client (admin only)
 *
 * Returns JSON:
 *   { success: true }               — client changed, page reload recommended
 *   { success: false, error: "..." }
 */
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/lib/database.php';
require_once __DIR__ . '/lib/auth_helper.php';
require_once __DIR__ . '/lib/client_helper.php';

header('Content-Type: application/json');

// Require authentication for this endpoint
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

$client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;

if ($client_id > 0) {
    $ok = ClientHelper::setActiveClient($client_id);
    if (!$ok) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Client not found or permission denied']);
        exit;
    }
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'No client_id provided']);
