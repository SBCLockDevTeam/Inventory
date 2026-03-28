<?php
/**
 * Clients List Page
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';

$clients    = DatabaseHelper::queryAll("SELECT id, name, description, is_default FROM clients ORDER BY name", []);
$page_title = 'Clients';
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
    <div class="body-content">
        <div class="items-table-wrapper">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Default</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($clients)): ?>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><?php echo (int)$client['id']; ?></td>
                                <td><?php echo htmlspecialchars($client['name']); ?></td>
                                <td><?php echo htmlspecialchars($client['description'] ?? ''); ?></td>
                                <td><?php echo $client['is_default'] ? '✓ Yes' : 'No'; ?></td>
                                <td class="actions">
                                    <a href="<?php echo BASE_PATH; ?>/admin/clients/edit.php?id=<?php echo (int)$client['id']; ?>" class="btn btn-small">Edit</a>
                                    <a href="<?php echo BASE_PATH; ?>/admin/users/?client_id=<?php echo (int)$client['id']; ?>" class="btn btn-small">Users</a>
                                    <a href="<?php echo BASE_PATH; ?>/admin/clients/delete.php?id=<?php echo (int)$client['id']; ?>" class="btn btn-small btn-danger">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="no-results">No clients found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="actions-bottom">
            <a href="<?php echo BASE_PATH; ?>/admin/clients/add.php" class="btn btn-primary">+ Create New Client</a>
        </div>
    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
