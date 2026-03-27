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
        <li><a href="<?php echo BASE_PATH; ?>/admin/clients/">Clients</a>
            <ul class="submenu">
                <li><a href="<?php echo BASE_PATH; ?>/admin/clients/add.php">Add Client</a></li>
                <li><a href="<?php echo BASE_PATH; ?>/admin/clients/">View Clients</a></li>
            </ul>
        </li>
        <li><a href="<?php echo BASE_PATH; ?>/admin/users/">Users</a>
            <ul class="submenu">
                <li><a href="<?php echo BASE_PATH; ?>/admin/users/add.php">Add User</a></li>
                <li><a href="<?php echo BASE_PATH; ?>/admin/users/">View Users</a></li>
            </ul>
        </li>
        <li><a href="#">Reports</a></li>
        <li><a href="#">Settings</a></li>
    </ul>
</nav>