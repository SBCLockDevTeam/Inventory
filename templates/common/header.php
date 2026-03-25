<?php
/**
 * Header Template
 */
$config = include __DIR__ . '/../../config/settings.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['app_name'] ?? 'SBC Inventory'); ?></title>
    <link rel="stylesheet" href="/qr/css/style.css">
</head>
<body>
<header class="main-header">
    <div class="header-container">
        <div class="logo">
            <h1><?php echo htmlspecialchars($config['app_name'] ?? 'SBC Inventory'); ?></h1>
            <p class="tagline"><?php echo htmlspecialchars($config['tagline'] ?? 'Security Building Controls'); ?></p>
        </div>
        <div class="header-actions">
            <select id="brand-selector" class="brand-selector">
                <option value="sbc">Security Building Controls</option>
                <option value="other">Other Brand</option>
            </select>
        </div>
    </div>
</header>
