<?php
/**
 * AJAX endpoint: set the active client and/or user in the session.
 * Called by the client/user selectors in the header.
 *
 * POST params:
 *   client_id (int, optional) — switch to a different client (also clears user)
 *   user_id   (int, optional) — switch to a different user within the active client
 *
 * Returns JSON:
 *   { success: true }                       — client changed, page reload recommended
 *   { success: true, redirect: "<url>" }    — user changed, caller must redirect to home
 *   { success: false, error: "..." }
 */
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/lib/database.php';
require_once __DIR__ . '/lib/client_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
$user_id   = isset($_POST['user_id'])   ? (int)$_POST['user_id']   : 0;

if ($client_id > 0) {
    $ok = ClientHelper::setActiveClient($client_id);
    if (!$ok) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Client not found']);
        exit;
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($user_id > 0) {
    $ok = ClientHelper::setActiveUser($user_id);
    if (!$ok) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    // Changing user always redirects to home
    echo json_encode(['success' => true, 'redirect' => BASE_PATH . '/']);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'No client_id or user_id provided']);
