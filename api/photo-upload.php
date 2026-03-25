<?php
/**
 * Photo Upload API Endpoint
 * Handles photo uploads from mobile camera and file browser
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/photo_handler.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get parameters
$item_id = $_POST['item_id'] ?? '';
$field_name = $_POST['field_name'] ?? '';
$capture_type = $_POST['capture_type'] ?? 'file';

// Validate
if (empty($item_id) || empty($field_name)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Verify item exists
$item = queryOne($db, "SELECT item_id FROM items WHERE item_id = ?", [$item_id]);
if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit;
}

try {
    $result = null;
    
    if ($capture_type === 'camera' && isset($_POST['photo_data'])) {
        $result = handleCameraCapture($_POST['photo_data'], $item_id, $field_name);
    } elseif ($capture_type === 'file' && isset($_FILES['photo_file'])) {
        $result = handlePhotoUpload($_FILES['photo_file'], $item_id, $field_name);
    } else {
        echo json_encode(['success' => false, 'message' => 'No photo data provided']);
        exit;
    }
    
    // Save to database
    if ($result['success']) {
        $existing = queryOne($db, 
            "SELECT field_value FROM item_fields WHERE item_id = ? AND field_name = ?",
            [$item_id, $field_name]
        );
        
        if ($existing) {
            $old_filename = basename($existing['field_value']);
            if (!empty($old_filename)) {
                deletePhoto($old_filename);
            }
            execute($db,
                "UPDATE item_fields SET field_value = ?, updated_at = NOW() WHERE item_id = ? AND field_name = ?",
                [$result['photo_path'], $item_id, $field_name]
            );
        } else {
            execute($db,
                "INSERT INTO item_fields (item_id, field_name, field_type, field_value, created_at, updated_at) VALUES (?, ?, 'photo', ?, NOW(), NOW())",
                [$item_id, $field_name, $result['photo_path']]
            );
        }
        
        logActivity("Photo uploaded for item {$item_id}, field {$field_name}");
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    logActivity("Photo upload error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}