<?php
/**
 * Header Template
 * 
 * Displays site header with logo/branding
 * Brand selector dropdown in top right
 */

// Default brand if not set
$current_brand = $_SESSION['brand'] ?? 'Default';
?>
<header class="site-header">
    <div class="header-container">
        <div class="logo">
            <h1>SBC Inventory System</h1>
        </div>
        
        <div class="brand-selector">
            <label for="brand-dropdown">Brand:</label>
            <select id="brand-dropdown" name="brand" onchange="changeBrand(this.value)">
                <option value="default" <?php echo ($current_brand === 'Default') ? 'selected' : ''; ?>>Default</option>
            </select>
        </div>
    </div>
</header>
