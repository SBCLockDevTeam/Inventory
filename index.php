<?php
/**
 * Main Entry Point
 * Shows all root items (top-level containers).
 */
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/lib/database.php';
require_once __DIR__ . '/lib/client_helper.php';

$active_user = ClientHelper::getActiveUser();

// Load all root items (self-parenting)
$root_items = DatabaseHelper::queryAll(
    "SELECT public_code, name, description, is_container
       FROM items
      WHERE location_item_id = public_code
      ORDER BY name",
    []
);

$page_title = 'QR Inventory System';
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
<body data-base-path="<?php echo BASE_PATH; ?>">
    <?php include __DIR__ . '/templates/common/header.php'; ?>
    <?php include __DIR__ . '/templates/common/menu.php'; ?>
    <div class="body-content">
        <h1>QR Inventory</h1>
        <?php if (!empty($root_items)): ?>
            <div class="items-table-wrapper">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($root_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                                <td><?php echo $item['is_container'] ? 'Container' : 'Item'; ?></td>
                                <td class="actions">
                                    <a href="<?php echo BASE_PATH; ?>/admin/items/view.php?id=<?php echo $item['public_code']; ?>" class="btn btn-small">View</a>
                                    <a href="<?php echo BASE_PATH; ?>/admin/items/edit.php?id=<?php echo $item['public_code']; ?>" class="btn btn-small">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No items found.</p>
        <?php endif; ?>
        <div class="actions-bottom">
            <a href="<?php echo BASE_PATH; ?>/admin/items/add.php" class="btn btn-primary">+ Add Item</a>
            <a href="<?php echo BASE_PATH; ?>/admin/items/" class="btn btn-secondary">View All Items</a>
        </div>
    </div>
    <?php include __DIR__ . '/templates/common/footer.php'; ?>
