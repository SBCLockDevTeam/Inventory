<?php
/**
 * Common page header template.
 * Renders the site header with client and user selectors.
 * The header displays "Client Name - User Name".
 * Changing the user redirects to the home page.
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/client_helper.php';

$page_title      = $page_title ?? 'QR Inventory System';
$active_client   = ClientHelper::getActiveClient();
$active_user     = ClientHelper::getActiveUser();
$all_clients     = ClientHelper::getAllClients();
$active_client_id = $active_client ? (int)$active_client['id'] : 0;
$active_user_id   = $active_user   ? (int)$active_user['id']   : 0;
$all_users        = $active_client ? ClientHelper::getAllUsersForClient($active_client_id) : [];

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
            <?php if (!empty($all_clients)): ?>
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

            <?php if (!empty($all_users)): ?>
            <label for="user-select">User:</label>
            <select id="user-select"
                    name="user_id"
                    data-set-user-url="<?php echo BASE_PATH; ?>/set_user.php"
                    data-home-url="<?php echo BASE_PATH; ?>/">
                <?php foreach ($all_users as $u): ?>
                    <option value="<?php echo (int)$u['id']; ?>"
                        <?php echo ($active_user_id === (int)$u['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($u['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
        </div>
    </div>
</header>