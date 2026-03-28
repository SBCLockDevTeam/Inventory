<?php
/**
 * Users List Page
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';

$users = DatabaseHelper::queryAll(
    "SELECT id, name, email, is_default, is_admin FROM users ORDER BY name",
    []
);

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
    <h1>Users</h1>
    <div class="body-content">
        <div class="items-table-wrapper">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User Name</th>
                        <th>Email</th>
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
                                <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
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
            <a href="<?php echo BASE_PATH; ?>/admin/users/add.php" class="btn btn-primary">+ Create New User</a>
        </div>
    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
