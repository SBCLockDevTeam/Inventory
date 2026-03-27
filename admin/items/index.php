<?php
/**
 * Items List Page
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';

$search = FormHelper::getGet('search', '');

$sql    = "SELECT public_code, name, is_container FROM items WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql   .= " AND (public_code LIKE ? OR name LIKE ? OR description LIKE ?)";
    $term   = '%' . $search . '%';
    $params = [$term, $term, $term];
}

$sql .= " ORDER BY name";

$items      = DatabaseHelper::queryAll($sql, $params);
$page_title = 'Items';
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
    <h1>Items</h1>
    <div class="body-content">
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <div class="form-group">
                    <input type="text" name="search" placeholder="Search by ID, name, or description"
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="<?php echo BASE_PATH; ?>/admin/items/" class="btn btn-secondary">Clear</a>
            </form>
        </div>
        <div class="items-table-wrapper">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item ID</th>
                        <th>Name</th>
                        <th>Container</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($item['public_code']); ?></code></td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo ($item['is_container'] == 1) ? '✓ Yes' : 'No'; ?></td>
                                <td class="actions">
                                    <a href="<?php echo BASE_PATH; ?>/admin/items/view.php?id=<?php echo $item['public_code']; ?>" class="btn btn-small">View</a>
                                    <a href="<?php echo BASE_PATH; ?>/admin/items/edit.php?id=<?php echo $item['public_code']; ?>" class="btn btn-small">Edit</a>
                                    <a href="<?php echo BASE_PATH; ?>/admin/items/delete.php?id=<?php echo $item['public_code']; ?>" class="btn btn-small btn-danger">Delete</a>
                                    <a href="<?php echo BASE_PATH; ?>/admin/items/fields.php?id=<?php echo $item['public_code']; ?>" class="btn btn-small">Fields</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="no-results">No items found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="actions-bottom">
            <a href="<?php echo BASE_PATH; ?>/admin/items/add.php" class="btn btn-primary">+ Create New Item</a>
        </div>
    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>