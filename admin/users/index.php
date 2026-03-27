<?php
/**
 * Users List Page
 * Optionally filtered by client_id via GET param.
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';

$client_id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;

$clients = DatabaseHelper::queryAll("SELECT id, name FROM clients ORDER BY name", []);

if ($client_id > 0) {
    $users = DatabaseHelper::queryAll(
        "SELECT u.id, u.name, u.is_default, u.is_admin, c.name AS client_name, u.client_id
           FROM users u
           JOIN clients c ON c.id = u.client_id
          WHERE u.client_id = ?
          ORDER BY u.name",
        [$client_id]
    );
    $filter_client = DatabaseHelper::queryOne("SELECT id, name FROM clients WHERE id = ?", [$client_id]);
} else {
    $users = DatabaseHelper::queryAll(
        "SELECT u.id, u.name, u.is_default, u.is_admin, c.name AS client_name, u.client_id
           FROM users u
           JOIN clients c ON c.id = u.client_id
          ORDER BY c.name, u.name",
        []
    );
    $filter_client = null;
}

$page_title = 'Users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/table.css">
    <script src="<?php echo JS_PATH; ?>script.js" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../templates/common/header.php'; ?>
    <?php include __DIR__ . '/../../templates/common/menu.php'; ?>
    <div id="error-division" class="error-banner" style="display: none;"></div>
    <h1>Users<?php if ($filter_client): ?> — <?php echo htmlspecialchars($filter_client['name']); ?><?php endif; ?></h1>
    <div class="body-content">
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <div class="form-group">
                    <label for="client_id">Filter by Client:</label>
                    <select id="client_id" name="client_id">
                        <option value="0">— All Clients —</option>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>"
                                <?php echo ($client_id === (int)$c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?php echo BASE_PATH; ?>/admin/users/" class="btn btn-secondary">Clear</a>
            </form>
        </div>
        <div class="items-table-wrapper">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User Name</th>
                        <th>Client</th>
                        <th>Default</th>
                        <th>Admin</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo (int)$user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['client_name']); ?></td>
                                <td><?php echo $user['is_default'] ? '✓ Yes' : 'No'; ?></td>
                                <td><?php echo $user['is_admin'] ? '✓ Yes' : 'No'; ?></td>
                                <td class="actions">
                                    <a href="<?php echo BASE_PATH; ?>/admin/users/edit.php?id=<?php echo (int)$user['id']; ?>" class="btn btn-small">Edit</a>
                                    <a href="<?php echo BASE_PATH; ?>/admin/users/delete.php?id=<?php echo (int)$user['id']; ?>" class="btn btn-small btn-danger">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-results">No users found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="actions-bottom">
            <a href="<?php echo BASE_PATH; ?>/admin/users/add.php<?php echo $client_id > 0 ? '?client_id=' . $client_id : ''; ?>" class="btn btn-primary">+ Create New User</a>
            <a href="<?php echo BASE_PATH; ?>/admin/clients/" class="btn btn-secondary">Back to Clients</a>
        </div>
    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
