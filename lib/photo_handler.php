<?php

function uploadPhoto($fileData, $item_id) {
    // Validate image type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($fileData['type'], $allowedTypes)) {
        return ['error' => 'Invalid file type.'];
    }

    // Validate image size (max 2MB)
    if ($fileData['size'] > 2 * 1024 * 1024) {
        return ['error' => 'File size exceeds 2MB.'];
    }

    // Generate unique filename
    $uniqueName = uniqid('photo_') . '.' . pathinfo($fileData['name'], PATHINFO_EXTENSION);
    $uploadDir = 'uploads/photos/';
    $fullPath = $uploadDir . $uniqueName;

    // Save to uploads/photos directory
    if (move_uploaded_file($fileData['tmp_name'], $fullPath)) {
        // Create thumbnail
        createThumbnail($fullPath);
        return ['path' => $fullPath];
    } else {
        return ['error' => 'Failed to upload photo.'];
    }
}

function deletePhoto($photoPath) {
    if (file_exists($photoPath)) {
        unlink($photoPath);
        return ['success' => 'Photo deleted.'];
    } else {
        return ['error' => 'Photo does not exist.'];
    }
}

function getItemPhotos($item_id) {
    $uploadDir = 'uploads/photos/';
    $photos = glob($uploadDir . 'photo_' . $item_id . '*');
    return $photos;
}

function createThumbnail($fullPath) {
    // Implement thumbnail creation logic here
}

?>