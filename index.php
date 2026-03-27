<?php
/**
 * Main Entry Point
 * Shows the active client's root items (top-level containers).
 */
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/lib/database.php';
require_once __DIR__ . '/lib/client_helper.php';

$active_client = ClientHelper::getActiveClient();
$active_user   = ClientHelper::getActiveUser();

// Load root items (self-parenting) for the active client
$root_items = [];
if ($active_client) {
    $root_items = DatabaseHelper::queryAll(
        "SELECT public_code, name, description, is_container
           FROM items
          WHERE client_id = ?
            AND location_item_id = public_code
          ORDER BY name",
        [(int)$active_client['id']]
    );
}

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
        <?php if ($active_client): ?>
            <h1><?php echo htmlspecialchars($active_client['name']); ?></h1>
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
                <p>No root items found for this client.</p>
            <?php endif; ?>
            <div class="actions-bottom">
                <a href="<?php echo BASE_PATH; ?>/admin/items/add.php" class="btn btn-primary">+ Add Item</a>
                <a href="<?php echo BASE_PATH; ?>/admin/items/" class="btn btn-secondary">View All Items</a>
            </div>
        <?php else: ?>
            <h1>Welcome to QR Inventory</h1>
            <p>No clients configured yet. Please create a client first.</p>
            <a href="<?php echo BASE_PATH; ?>/admin/clients/add.php" class="btn btn-primary">Create Client</a>
        <?php endif; ?>
    </div>
    <?php include __DIR__ . '/templates/common/footer.php'; ?>
