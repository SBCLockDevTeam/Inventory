<?php
/**
 * Common menu template.
 * Included on every page between the header and error division.
 * Expects $menuItems (array) to be set before inclusion.
 *
 * $menuItems structure:
 *   [
 *     ['label' => 'Home', 'url' => '/qr/'],
 *     ['label' => 'Items', 'url' => '/qr/items/', 'children' => [
 *         ['label' => 'All Items', 'url' => '/qr/items/'],
 *         ['label' => 'Add Item',  'url' => '/qr/items/add.php'],
 *     ]],
 *   ]
 */
?>
<nav class="site-nav" role="navigation" aria-label="Main navigation">
    <!-- Hamburger button — visible only on mobile via CSS -->
    <button class="nav-hamburger" id="nav-hamburger"
            aria-expanded="false" aria-controls="nav-menu" aria-label="Toggle menu">
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
    </button>

    <ul class="nav-menu" id="nav-menu" role="menubar">
        <?php foreach ($menuItems ?? [] as $item): ?>
            <?php $hasChildren = !empty($item['children']); ?>
            <li class="nav-item<?= $hasChildren ? ' has-dropdown' : '' ?>" role="none">
                <a href="<?= htmlspecialchars($item['url']) ?>"
                   class="nav-link"
                   role="menuitem"
                   <?= $hasChildren ? 'aria-haspopup="true" aria-expanded="false"' : '' ?>
                >
                    <?= htmlspecialchars($item['label']) ?>
                    <?php if ($hasChildren): ?>
                        <span class="nav-arrow" aria-hidden="true">&#9660;</span>
                    <?php endif; ?>
                </a>
                <?php if ($hasChildren): ?>
                    <ul class="nav-dropdown" role="menu">
                        <?php foreach ($item['children'] as $child): ?>
                            <li role="none">
                                <a href="<?= htmlspecialchars($child['url']) ?>"
                                   class="nav-dropdown-link"
                                   role="menuitem">
                                    <?= htmlspecialchars($child['label']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
<script src="<?= BASE_URL ?>/js/lib/menu.js" defer></script>
