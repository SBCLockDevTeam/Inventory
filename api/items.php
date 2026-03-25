<?php
header('Content-Type: application/json');
require '../../lib/database.php';

$db = new Database();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $code = $_GET['code'] ?? null;
    
    if ($code) {
        $db->query("SELECT * FROM items WHERE public_code = :code");
        $db->bind(':code', $code);
        $item = $db->queryOne();
        
        if ($item) {
            echo json_encode(['success' => true, 'data' => $item]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Item not found']);
        }
    } else {
        $db->query("SELECT * FROM items ORDER BY created_at DESC");
        $items = $db->queryAll();
        echo json_encode(['success' => true, 'data' => $items]);
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $db->query("INSERT INTO items (name, description, brand_id, public_code, is_container) VALUES (:name, :description, :brand_id, :public_code, :is_container)");
    $db->bind(':name', $data['name']);
    $db->bind(':description', $data['description'] ?? null);
    $db->bind(':brand_id', $data['brand_id'] ?? null);
    $db->bind(':public_code', $data['public_code']);
    $db->bind(':is_container', $data['is_container'] ?? 0);
    
    if ($db->execute()) {
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Item created']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error creating item']);
    }
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        $db->query("UPDATE items SET name = :name, description = :description, brand_id = :brand_id, is_container = :is_container WHERE id = :id");
        $db->bind(':id', $id);
        $db->bind(':name', $data['name']);
        $db->bind(':description', $data['description'] ?? null);
        $db->bind(':brand_id', $data['brand_id'] ?? null);
        $db->bind(':is_container', $data['is_container'] ?? 0);
        
        if ($db->execute()) {
            echo json_encode(['success' => true, 'message' => 'Item updated']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error updating item']);
        }
    }
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        $db->query("DELETE FROM items WHERE id = :id");
        $db->bind(':id', $id);
        
        if ($db->execute()) {
            echo json_encode(['success' => true, 'message' => 'Item deleted']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error deleting item']);
        }
    }
}
?>
