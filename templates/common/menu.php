<?php
/**
 * Menu Template
 * 
 * Primary horizontal navigation menu with dropdowns
 * Menu items data-driven based on user role
 */
?>
<nav class="main-menu">
    <ul class="menu-items">
        <li class="menu-item">
            <a href="/qr/admin/">Dashboard</a>
        </li>
        <li class="menu-item">
            <a href="/qr/admin/items/">Items</a>
            <ul class="submenu">
                <li><a href="/qr/admin/items/">List Items</a></li>
                <li><a href="/qr/admin/items/add.php">Add Item</a></li>
            </ul>
        </li>
        <li class="menu-item">
            <a href="/qr/admin/brands/">Brands</a>
        </li>
        <li class="menu-item">
            <a href="/qr/admin/logs/">Logs</a>
        </li>
    </ul>
</nav>
