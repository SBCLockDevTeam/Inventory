<?php
/**
 * Menu Template
 * Primary navigation menu with dropdown support and responsive design
 */

// Get current page for active state highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
$rootUrl = $config['root_url'] ?? '/qr/';

// Define menu structure with dropdowns
$menuItems = [
    [
        'label' => 'Home',
        'url' => $rootUrl . 'public/index.php',
        'page' => 'index.php',
        'icon' => '🏠',
        'children' => []
    ],
    [
        'label' => 'Inventory',
        'url' => $rootUrl . 'public/inventory.php',
        'page' => 'inventory.php',
        'icon' => '📦',
        'children' => [
            [
                'label' => 'View All Items',
                'url' => $rootUrl . 'public/inventory.php',
                'page' => 'inventory.php'
            ],
            [
                'label' => 'Add New Item',
                'url' => $rootUrl . 'public/items/add.php',
                'page' => 'add.php'
            ],
            [
                'label' => 'Search Items',
                'url' => $rootUrl . 'public/search.php',
                'page' => 'search.php'
            ]
        ]
    ],
    [
        'label' => 'Reports',
        'url' => $rootUrl . 'public/statistics.php',
        'page' => 'statistics.php',
        'icon' => '📊',
        'children' => [
            [
                'label' => 'Statistics',
                'url' => $rootUrl . 'public/statistics.php',
                'page' => 'statistics.php'
            ],
            [
                'label' => 'Logs',
                'url' => $rootUrl . 'public/logs.php',
                'page' => 'logs.php'
            ]
        ]
    ],
    [
        'label' => 'Admin',
        'url' => $rootUrl . 'public/admin/dashboard.php',
        'page' => 'dashboard.php',
        'icon' => '⚙️',
        'role' => 'admin',
        'children' => [
            [
                'label' => 'Manage Brands',
                'url' => $rootUrl . 'public/admin/brands.php',
                'page' => 'brands.php'
            ],
            [
                'label' => 'Manage Profiles',
                'url' => $rootUrl . 'public/profiles.php',
                'page' => 'profiles.php'
            ],
            [
                'label' => 'System Settings',
                'url' => $rootUrl . 'public/admin/settings.php',
                'page' => 'settings.php'
            ]
        ]
    ]
];

/**
 * Check if a menu item should be displayed based on user role
 * @param array $item Menu item configuration
 * @return bool
 */
function shouldDisplayMenuItem($item) {
    // If no role requirement, show to everyone
    if (!isset($item['role'])) {
        return true;
    }
    
    // Check if user has required role
    // TODO: Implement proper authentication system
    // For now, show all items
    return true;
}

/**
 * Check if current page matches menu item
 * @param array $item Menu item configuration
 * @param string $currentPage Current page filename
 * @return bool
 */
function isActivePage($item, $currentPage) {
    if ($item['page'] === $currentPage) {
        return true;
    }
    
    // Check children
    if (!empty($item['children'])) {
        foreach ($item['children'] as $child) {
            if ($child['page'] === $currentPage) {
                return true;
            }
        }
    }
    
    return false;
}
?>

<nav class="main-nav" role="navigation" aria-label="Main navigation">
    <!-- Mobile menu toggle button -->
    <button class="mobile-menu-toggle" aria-label="Toggle menu" aria-expanded="false" onclick="toggleMobileMenu()">
        <span class="hamburger-icon">
            <span></span>
            <span></span>
            <span></span>
        </span>
    </button>
    
    <ul class="nav-list">
        <?php foreach ($menuItems as $item): ?>
            <?php if (shouldDisplayMenuItem($item)): ?>
                <li class="nav-item <?php echo !empty($item['children']) ? 'has-dropdown' : ''; ?> <?php echo isActivePage($item, $currentPage) ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                       class="nav-link"
                       <?php if (!empty($item['children'])): ?>
                           aria-haspopup="true" aria-expanded="false"
                       <?php endif; ?>
                       >
                        <span class="nav-icon"><?php echo $item['icon']; ?></span>
                        <span class="nav-label"><?php echo htmlspecialchars($item['label']); ?></span>
                        <?php if (!empty($item['children'])): ?>
                            <span class="dropdown-arrow">▼</span>
                        <?php endif; ?>
                    </a>
                    
                    <?php if (!empty($item['children'])): ?>
                        <ul class="nav-dropdown">
                            <?php foreach ($item['children'] as $child): ?>
                                <li class="nav-dropdown-item">
                                    <a href="<?php echo htmlspecialchars($child['url']); ?>" 
                                       class="nav-dropdown-link <?php echo ($child['page'] === $currentPage) ? 'active' : ''; ?>">
                                        <?php echo htmlspecialchars($child['label']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</nav>

<script>
/**
 * Toggle mobile menu visibility
 */
function toggleMobileMenu() {
    const nav = document.querySelector('.main-nav');
    const toggle = document.querySelector('.mobile-menu-toggle');
    const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
    
    nav.classList.toggle('mobile-open');
    toggle.setAttribute('aria-expanded', !isExpanded);
    toggle.classList.toggle('active');
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(event) {
    const nav = document.querySelector('.main-nav');
    const toggle = document.querySelector('.mobile-menu-toggle');
    
    if (nav.classList.contains('mobile-open') && 
        !nav.contains(event.target) && 
        !toggle.contains(event.target)) {
        toggleMobileMenu();
    }
});

// Handle dropdown menus on desktop
document.querySelectorAll('.nav-item.has-dropdown > .nav-link').forEach(link => {
    link.addEventListener('click', function(e) {
        // On mobile, prevent default and toggle dropdown
        if (window.innerWidth <= 768) {
            e.preventDefault();
            const parent = this.parentElement;
            parent.classList.toggle('dropdown-open');
            
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !isExpanded);
        }
    });
});
</script>