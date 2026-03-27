<?php
/**
 * Common page header template.
 * Renders the site header including the brand selector dropdown (top right).
 * Brand selection affects only the visual theme (header, footer, CSS variables);
 * it has no relation to inventory items or their contents.
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/brand_helper.php';

$page_title     = $page_title ?? 'QR Inventory System';
$active_brand   = BrandHelper::getActiveBrand();
$all_brands     = BrandHelper::getAllBrands();
$active_brand_id = $active_brand ? (int)$active_brand['id'] : 0;
?>
<header class="site-header" data-brand="<?php echo $active_brand_id; ?>">
    <div class="header-content">
        <div class="header-logo">
            <span class="header-site-name"><?php echo htmlspecialchars($active_brand ? $active_brand['name'] : 'QR Inventory'); ?></span>
        </div>
        <?php if (!empty($all_brands)): ?>
        <div class="brand-selector">
            <label for="brand-select">Brand:</label>
            <select id="brand-select"
                    name="brand_id"
                    data-set-brand-url="<?php echo BASE_PATH; ?>/set_brand.php">
                <?php foreach ($all_brands as $brand): ?>
                    <option value="<?php echo (int)$brand['id']; ?>"
                        <?php echo ($active_brand_id === (int)$brand['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($brand['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>
</header>