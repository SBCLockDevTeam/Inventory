<?php
require '../lib/database.php';
require '../lib/logger.php';

header('Content-Type: application/json');

$db = new Database();
$logger = new Logger('../logs/activity.log');

$itemCode = $_GET['code'] ?? null;

if (!$itemCode) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Item code is required']);
    exit;
}

// Get item
$db->query("SELECT * FROM items WHERE public_code = :code");
$db->bind(':code', $itemCode);
$item = $db->queryOne();

if (!$item) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit;
}

// Record scan
$db->query("INSERT INTO qr_scans (item_id, item_code, ip_address, user_agent) VALUES (:item_id, :item_code, :ip_address, :user_agent)");
$db->bind(':item_id', $item['id']);
$db->bind(':item_code', $itemCode);
$db->bind(':ip_address', $_SERVER['REMOTE_ADDR']);
$db->bind(':user_agent', $_SERVER['HTTP_USER_AGENT']);
$db->execute();

$logger->log('QR code scanned', 'INFO', ['item_code' => $itemCode]);

echo json_encode(['success' => true, 'data' => $item]);
?>
