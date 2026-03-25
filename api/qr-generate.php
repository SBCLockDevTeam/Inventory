<?php
declare(strict_types=1);

header('Content-Type: application/json');
require_once __DIR__ . '/../lib/bootstrap.php';

try {
    $db = db();
    $itemCode = $_GET['code'] ?? null;

    if (!$itemCode) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Item code is required']);
        exit;
    }

    $db->query('SELECT * FROM items WHERE public_code = :code');
    $db->bind(':code', $itemCode);
    $item = $db->queryOne();

    if (!$item) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $item]);
} catch (Throwable $e) {
    log_exception($e, 'api_qr_generate');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
