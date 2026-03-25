<?php
declare(strict_types=1);

header('Content-Type: application/json');
require_once __DIR__ . '/../lib/bootstrap.php';

$db = db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $code = $_GET['code'] ?? null;

        if ($code) {
            $db->query('SELECT * FROM items WHERE public_code = :code');
            $db->bind(':code', $code);
            $item = $db->queryOne();

            if (!$item) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Item not found']);
                exit;
            }

            echo json_encode(['success' => true, 'data' => $item]);
            exit;
        }

        $db->query('SELECT * FROM items ORDER BY created_at DESC');
        $items = $db->queryAll();
        echo json_encode(['success' => true, 'data' => $items]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed (GET only for now)']);
} catch (Throwable $e) {
    log_exception($e, 'api_items');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
