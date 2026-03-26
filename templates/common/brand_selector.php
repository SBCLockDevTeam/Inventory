<?php
/**
 * Brand selector dropdown.
 * Rendered in the top-right of the header on every page.
 *
 * Expects:
 *   $brands      (array)  - list of available brands: [['id'=>int, 'name'=>string], ...]
 *   $activeBrand (int)    - ID of the currently selected brand
 *
 * On change, posts selected brand ID to /api/set_brand.php via AJAX,
 * then reloads the current page so branding refreshes.
 */
$brands      = $brands      ?? [];
$activeBrand = $activeBrand ?? 0;
?>
<form class="brand-selector-form" id="brand-selector-form" aria-label="Brand selection">
    <label for="brand-select" class="brand-selector-label">Brand:</label>
    <select id="brand-select" name="brand_id" class="brand-selector-select">
        <?php foreach ($brands as $b): ?>
            <option value="<?= (int) $b['id'] ?>"
                <?= ((int) $b['id'] === (int) $activeBrand) ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>
