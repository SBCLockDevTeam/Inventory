<?php
/**
 * AJAX endpoint: set the active brand in the user's session.
 * Called by the brand selector dropdown in the header.
 * Returns JSON { success: true } or { success: false, error: "..." }
 * Brand selection affects only the visual theme; it has no relation to inventory items.
 */
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/lib/database.php';
require_once __DIR__ . '/lib/brand_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$brand_id = isset($_POST['brand_id']) ? (int)$_POST['brand_id'] : 0;

if ($brand_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid brand ID']);
    exit;
}

$ok = BrandHelper::setActiveBrand($brand_id);

if ($ok) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Brand not found']);
}
