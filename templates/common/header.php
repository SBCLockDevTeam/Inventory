<?php
// Common header template
require_once __DIR__ . '/../../config/settings.php';
$page_title = $page_title ?? 'QR Inventory System';
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
<header>
    <div class="header-content">
        <div class="logo">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>
        </div>
        <div class="brand-selector">
            <label for="brand-select">Brand:</label>
            <select id="brand-select">
                <option value="default">Default Brand</option>
            </select>
        </div>
    </div>
</header>