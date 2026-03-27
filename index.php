<?php
/**
 * Main Entry Point
 */
require_once __DIR__ . '/config/settings.php';

$page_title = 'QR Inventory System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <script src="<?php echo JS_PATH; ?>script.js" defer></script>
</head>
<body>
    <?php include __DIR__ . '/templates/common/header.php'; ?>
    <?php include __DIR__ . '/templates/common/menu.php'; ?>
    <div class="body-content">
        <h1>Welcome to QR Inventory</h1>
        <p>Use the menu above or the link below to manage inventory items.</p>
        <a href="<?php echo BASE_PATH; ?>/admin/items/" class="btn btn-primary">View Items</a>
    </div>
    <?php include __DIR__ . '/templates/common/footer.php'; ?>
