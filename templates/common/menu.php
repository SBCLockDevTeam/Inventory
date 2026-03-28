<?php
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/client_helper.php';
$_menu_is_admin = ClientHelper::isActiveUserAdmin();
?>
<nav class="menu">
    <button class="hamburger-btn" id="menu-hamburger" aria-expanded="false" aria-controls="main-menu-list">☰</button>
    <ul class="menu-list" id="main-menu-list">
        <li><a href="<?php echo BASE_PATH; ?>/">Home</a></li>
        <li><a href="<?php echo BASE_PATH; ?>/items/">Items</a>
            <ul class="submenu">
                <li><a href="<?php echo BASE_PATH; ?>/admin/items/add.php">Add Item</a></li>
                <li><a href="<?php echo BASE_PATH; ?>/items/">View Items</a></li>
            </ul>
        </li>
        <?php if ($_menu_is_admin): ?>
        <li><a href="#">Admin</a>
            <ul class="submenu">
                <li><a href="<?php echo BASE_PATH; ?>/admin/users/add.php">Add User</a></li>
                <li><a href="<?php echo BASE_PATH; ?>/admin/users/">View Users</a></li>
                <li><a href="<?php echo BASE_PATH; ?>/admin/printers/">Printers</a></li>
                <li><a href="<?php echo BASE_PATH; ?>/admin/logs/exceptions.php">Exceptions Log</a></li>
                <li><a href="<?php echo BASE_PATH; ?>/admin/logs/">General Log</a></li>
                <li><a href="#" class="menu-item-disabled">Settings</a></li>
            </ul>
        </li>
        <?php endif; ?>
    </ul>
</nav>