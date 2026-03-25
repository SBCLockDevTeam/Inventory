<?php
/**
 * Header Template
 * Main page header with branding and navigation
 */
?>
<header class="main-header">
    <div class="header-container">
        <div class="logo">
            <h1>SBC Inventory</h1>
            <p class="tagline">Security Building Controls</p>
        </div>
        <div class="header-actions">
            <select id="brand-selector" class="brand-selector" onchange="changeBrand(this.value)">
                <option value="sbc">Security Building Controls</option>
                <option value="other">Other Brand</option>
            </select>
        </div>
    </div>
</header>