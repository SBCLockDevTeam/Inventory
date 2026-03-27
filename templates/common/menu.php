<?php require_once __DIR__ . '/../../config/settings.php'; ?>
<nav class="menu">
    <ul class="menu-list">
        <li><a href="<?php echo BASE_PATH; ?>/">Home</a></li>
        <li><a href="<?php echo BASE_PATH; ?>/admin/items/">Items</a>
            <ul class="submenu">
                <li><a href="<?php echo BASE_PATH; ?>/admin/items/add.php">Add Item</a></li>
                <li><a href="<?php echo BASE_PATH; ?>/admin/items/">View Items</a></li>
            </ul>
        </li>
        <li><a href="#">Reports</a></li>
        <li><a href="#">Settings</a></li>
    </ul>
</nav>