<?php
// Common header template
require_once __DIR__ . '/../../config/settings.php';
$page_title = $page_title ?? 'QR Inventory System';
?>
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