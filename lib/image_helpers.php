<?php
/**
 * Image Upload Helper Functions
 *
 * Handles uploading, validating, and deleting item primary images.
 */

/**
 * Validate and move an uploaded image to /uploads/items/.
 * Returns the web-relative path (e.g. '/uploads/items/item_abc123.jpg').
 *
 * @throws RuntimeException on validation or file-system failures.
 */
function handleImageUpload(array $file): string
{
    $upload_dir = __DIR__ . '/../uploads/items/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new RuntimeException('Cannot create upload directory');
        }
    }

    // Validate via finfo (not the user-supplied MIME type)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Invalid image type. Allowed: JPEG, PNG, GIF, WebP');
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Image size exceeds 5 MB limit');
    }

    $ext      = $allowed[$mime];
    $filename = uniqid('item_', true) . '.' . $ext;
    $dest     = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Failed to save uploaded image');
    }

    return '/uploads/items/' . $filename;
}

/**
 * Delete an image file from the filesystem.
 * $path is the web-relative path (e.g. '/uploads/items/item_abc.jpg').
 */
function deleteImage(string $path): bool
{
    if (empty($path)) {
        return false;
    }
    $full_path = __DIR__ . '/../' . ltrim($path, '/');
    if (file_exists($full_path)) {
        return unlink($full_path);
    }
    return false;
}
