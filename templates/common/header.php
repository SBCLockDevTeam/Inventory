<?php
/**
 * Common page header template.
 *
 * Enforces authentication via Microsoft Entra ID.
 * Displays the site name and a user avatar circle with a dropdown menu.
 * Admin users can switch the active client context from the avatar dropdown.
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/auth_helper.php';
require_once __DIR__ . '/../../lib/client_helper.php';

// Every page that includes this header requires authentication
AuthHelper::requireAuth();

$page_title       = $page_title ?? 'QR Inventory System';
$active_client    = ClientHelper::getActiveClient();
$active_user      = ClientHelper::getActiveUser();
$is_admin         = ClientHelper::isActiveUserAdmin();
$active_client_id = $active_client ? (int)$active_client['id'] : 0;

// Admins may switch client context from the avatar dropdown
$all_clients = $is_admin ? ClientHelper::getAllClients() : [];

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
                <?php if ($is_admin && !empty($all_clients)): ?>
                <div class="user-avatar-switch">
                    <button type="button" class="user-avatar-menu-item" id="switch-user-toggle"
                            aria-expanded="false">Switch User</button>
                    <div class="switch-user-clients" id="switch-user-clients" hidden>
                        <select id="client-select"
                                data-set-user-url="<?php echo BASE_PATH; ?>/set_user.php">
                            <?php foreach ($all_clients as $c): ?>
                                <option value="<?php echo (int)$c['id']; ?>"
                                    <?php echo ($active_client_id === (int)$c['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
                <ul class="user-avatar-menu">
                    <li><a href="<?php echo BASE_PATH; ?>/auth/logout.php" class="user-avatar-menu-item">Sign Out</a></li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</header>