<?php
/**
 * Common page header template.
 *
 * Enforces authentication via Microsoft Entra ID.
 * Displays "Client Name — User Name" using the authenticated session.
 * Admin users see a client-selector dropdown so they can switch clients.
 * Regular users see their assigned client name only (no dropdown).
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/auth_helper.php';
require_once __DIR__ . '/../../lib/client_helper.php';

// Every page that includes this header requires authentication
AuthHelper::requireAuth();

$page_title      = $page_title ?? 'QR Inventory System';
$active_client   = ClientHelper::getActiveClient();
$active_user     = ClientHelper::getActiveUser();
$is_admin        = ClientHelper::isActiveUserAdmin();
$active_client_id = $active_client ? (int)$active_client['id'] : 0;

// Admins see all clients; regular users stay locked to their own client
$all_clients = $is_admin ? ClientHelper::getAllClients() : [];

$header_label = '';
if ($active_client) {
    $header_label = htmlspecialchars($active_client['name']);
    if ($active_user) {
        $header_label .= ' — ' . htmlspecialchars($active_user['name']);
    }
}
?>
<header class="site-header">
    <script>/* Application base path for JS use */var BASE_PATH = <?php echo json_encode(BASE_PATH, JSON_HEX_TAG | JSON_HEX_AMP); ?>;</script>
    <div class="header-content">
        <div class="header-logo">
            <a href="<?php echo BASE_PATH; ?>/" class="header-site-name">
                <?php echo $header_label ?: 'QR Inventory'; ?>
            </a>
        </div>
        <div class="user-selector">
            <?php if ($is_admin && !empty($all_clients)): ?>
            <label for="client-select">Client:</label>
            <select id="client-select"
                    name="client_id"
                    data-set-user-url="<?php echo BASE_PATH; ?>/set_user.php">
                <?php foreach ($all_clients as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>"
                        <?php echo ($active_client_id === (int)$c['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <?php if ($active_user): ?>
            <span class="auth-user-name">
                <?php echo htmlspecialchars($active_user['name']); ?>
                <?php if (!empty($active_user['email'])): ?>
                <span class="auth-user-email">(<?php echo htmlspecialchars($active_user['email']); ?>)</span>
                <?php endif; ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
</header>