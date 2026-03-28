<?php
/**
 * Printer Management – List all printers.
 * Admin only.
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';
require_once __DIR__ . '/../../lib/client_helper.php';

if (!ClientHelper::isActiveUserAdmin()) {
    header('Location: ' . BASE_PATH . '/home.php');
    exit;
}

$printers   = DatabaseHelper::queryAll("SELECT * FROM printers ORDER BY sort_order, name", []);
$page_title = 'Printers';
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
    <h1>Printers</h1>
    <div class="body-content">
        <div class="item-actions">
            <a href="<?php echo BASE_PATH; ?>/admin/printers/add.php" class="btn btn-primary">Add Printer</a>
        </div>
        <div class="items-table-wrapper">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Hostname / URL</th>
                        <th>Port</th>
                        <th>Status</th>
                        <th>Default</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($printers)): ?>
                    <tr><td colspan="6">No printers defined yet.</td></tr>
                    <?php else: ?>
                    <?php foreach ($printers as $printer): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($printer['name']); ?></td>
                        <td><?php echo htmlspecialchars($printer['host']); ?></td>
                        <td><?php echo (int)$printer['port']; ?></td>
                        <td><?php echo $printer['is_active'] ? '<span class="status-active">Active</span>' : '<span class="status-inactive">Inactive</span>'; ?></td>
                        <td><?php echo $printer['is_default'] ? '✓' : ''; ?></td>
                        <td class="actions">
                            <a href="<?php echo BASE_PATH; ?>/admin/printers/edit.php?id=<?php echo (int)$printer['id']; ?>" class="btn btn-small">Edit</a>
                            <a href="<?php echo BASE_PATH; ?>/admin/printers/delete.php?id=<?php echo (int)$printer['id']; ?>" class="btn btn-small btn-danger">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
</body>
</html>
