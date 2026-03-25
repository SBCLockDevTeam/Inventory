<?php
/**
 * Menu Template
 * Primary navigation menu with dropdown support and responsive design.
 *
 * Expects $config and $rootUrl to already be in scope (set by header.php).
 * No inline JavaScript - JS is externalised to js/main.js.
 */

// $config is set in header.php; fall back safely if included standalone.
$config  = $config  ?? app_settings();
$rootUrl = $rootUrl ?? ($config['root_url'] ?? '/qr/');

// Base for all public page links
$pub = $rootUrl . 'public/';

// Current file name for active-state highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

/**
 * Return true if $item (or any of its children) matches the current page.
 *
 * @param array  $item        Menu item definition.
 * @param string $currentPage Basename of the current PHP file.
 * @return bool
 */
function isActivePage(array $item, string $currentPage): bool
{
    if (($item['page'] ?? '') === $currentPage) {
        return true;
    }
    foreach ($item['children'] ?? [] as $child) {
        if (($child['page'] ?? '') === $currentPage) {
            return true;
        }
    }
    return false;
}

// Menu structure - all URLs use the $pub base so they resolve correctly at any path depth.
$menuItems = [
    [
        'label'    => 'Home',
        'url'      => $pub . 'index.php',
        'page'     => 'index.php',
        'icon'     => '🏠',
        'children' => [],
    ],
    [
        'label' => 'Inventory',
        'url'   => $pub . 'inventory.php',
        'page'  => 'inventory.php',
        'icon'  => '📦',
        'children' => [
            ['label' => 'View All Items', 'url' => $pub . 'inventory.php',       'page' => 'inventory.php'],
            ['label' => 'Add New Item',   'url' => $pub . 'items/add.php',       'page' => 'add.php'],
            ['label' => 'Search Items',   'url' => $pub . 'search.php',          'page' => 'search.php'],
        ],
    ],
    [
        'label' => 'Reports',
        'url'   => $pub . 'statistics.php',
        'page'  => 'statistics.php',
        'icon'  => '📊',
        'children' => [
            ['label' => 'Statistics', 'url' => $pub . 'statistics.php', 'page' => 'statistics.php'],
            ['label' => 'Logs',       'url' => $pub . 'logs.php',       'page' => 'logs.php'],
        ],
    ],
    [
        'label' => 'Admin',
        'url'   => $pub . 'admin/dashboard.php',
        'page'  => 'dashboard.php',
        'icon'  => '⚙️',
        'role'  => 'admin',
        'children' => [
            ['label' => 'Manage Brands',   'url' => $pub . 'admin/brands.php',   'page' => 'brands.php'],
            ['label' => 'Manage Profiles', 'url' => $pub . 'profiles.php',       'page' => 'profiles.php'],
            ['label' => 'System Settings', 'url' => $pub . 'admin/settings.php', 'page' => 'settings.php'],
        ],
    ],
];
?>

<nav class="main-nav" role="navigation" aria-label="Main navigation">
    <!-- Mobile hamburger button - behaviour driven by js/main.js -->
    <button class="mobile-menu-toggle"
            aria-label="Toggle menu"
            aria-expanded="false"
            aria-controls="main-nav-list"
            type="button">
        <span class="hamburger-icon" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
        </span>
    </button>

    <ul class="nav-list" id="main-nav-list">
        <?php foreach ($menuItems as $item): ?>
            <li class="nav-item<?php echo !empty($item['children']) ? ' has-dropdown' : ''; ?><?php echo isActivePage($item, $currentPage) ? ' active' : ''; ?>">

                <a href="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>"
                   class="nav-link"
                   <?php if (!empty($item['children'])): ?>
                       aria-haspopup="true" aria-expanded="false"
                   <?php endif; ?>
                >
                    <span class="nav-icon" aria-hidden="true"><?php echo $item['icon']; ?></span>
                    <span class="nav-label"><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php if (!empty($item['children'])): ?>
                        <span class="dropdown-arrow" aria-hidden="true">▼</span>
                    <?php endif; ?>
                </a>

                <?php if (!empty($item['children'])): ?>
                    <ul class="nav-dropdown">
                        <?php foreach ($item['children'] as $child): ?>
                            <li class="nav-dropdown-item">
                                <a href="<?php echo htmlspecialchars($child['url'], ENT_QUOTES, 'UTF-8'); ?>"
                                   class="nav-dropdown-link<?php echo (($child['page'] ?? '') === $currentPage) ? ' active' : ''; ?>">
                                    <?php echo htmlspecialchars($child['label'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

            </li>
        <?php endforeach; ?>
    </ul>
</nav>
