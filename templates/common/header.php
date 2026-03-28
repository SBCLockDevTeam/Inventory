<?php
/**
 * Common page header template.
 *
 * Enforces authentication via Microsoft Entra ID.
 * Displays a user avatar circle with a dropdown menu (Sign Out).
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/auth_helper.php';
require_once __DIR__ . '/../../lib/client_helper.php';

// Every page that includes this header requires authentication
AuthHelper::requireAuth();

$page_title  = $page_title ?? 'QR Inventory System';
$active_user = ClientHelper::getActiveUser();

// Derive initials (up to 2 letters) from the user's display name
$avatar_initials = '';
if ($active_user) {
    $parts = preg_split('/\s+/', trim($active_user['name']));
    $avatar_initials = mb_strtoupper(mb_substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $avatar_initials .= mb_strtoupper(mb_substr(end($parts), 0, 1));
    }
}
?>
<header class="site-header">
    <script>/* Application base path for JS use */var BASE_PATH = <?php echo json_encode(BASE_PATH, JSON_HEX_TAG | JSON_HEX_AMP); ?>;</script>
    <div class="header-content">
        <div class="header-logo">
            <a href="<?php echo BASE_PATH; ?>/" class="header-site-name">QR Inventory</a>
        </div>
        <?php if ($active_user): ?>
        <div class="user-avatar-wrap" id="user-avatar-wrap">
            <button class="user-avatar-circle" id="user-avatar-toggle"
                    type="button" aria-haspopup="true" aria-expanded="false"
                    aria-label="User menu">
                <?php echo htmlspecialchars($avatar_initials ?: '?'); ?>
            </button>
            <div class="user-avatar-dropdown" id="user-avatar-dropdown" role="menu" aria-hidden="true">
                <div class="user-avatar-info">
                    <div class="user-avatar-fullname"><?php echo htmlspecialchars($active_user['name']); ?></div>
                    <?php if (!empty($active_user['email'])): ?>
                    <div class="user-avatar-email"><?php echo htmlspecialchars($active_user['email']); ?></div>
                    <?php endif; ?>
                </div>
                <ul class="user-avatar-menu">
                    <li><a href="<?php echo BASE_PATH; ?>/auth/logout.php" class="user-avatar-menu-item">Sign Out</a></li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</header>