<?php
// Common header template
$page_title = $page_title ?? 'QR Inventory System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <script src="/js/script.js" defer></script>
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