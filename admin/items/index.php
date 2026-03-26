<?php
/**
 * Items List Page
 */
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';

$search = FormHelper::getGet('search', '');
$brand_filter = FormHelper::getGet('brand', '');

$sql = "SELECT i.public_code, i.name, i.brand_id, i.is_container, b.name as brand_name FROM items i LEFT JOIN brands b ON i.brand_id = b.id WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND (i.public_code LIKE ? OR i.name LIKE ? OR i.description LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= 'sss';
}

if (!empty($brand_filter)) {
    $sql .= " AND i.brand_id = ?";
    $params[] = (int)$brand_filter;
    $types .= 'i';
}

$sql .= " ORDER BY i.brand_id, i.name";

$items = DatabaseHelper::queryAll($sql, $params, $types);
$brands = DatabaseHelper::queryAll("SELECT id, name FROM brands ORDER BY name", []);
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
                    <input type="text" name="search" placeholder="Search by ID, name, or description" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="form-group">
                    <select name="brand">
                        <option value="">-- All Brands --</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo $brand['id']; ?>" <?php echo ($brand_filter == $brand['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($brand['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?php echo BASE_PATH; ?>/admin/items/" class="btn btn-secondary">Clear</a>
            </form>
        </div>
        <div class="items-table-wrapper">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item ID</th>
                        <th>Name</th>
                        <th>Brand</th>
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
                                <td><?php echo htmlspecialchars($item['brand_name']); ?></td>
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
                            <td colspan="5" class="no-results">No items found</td>
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
</body>
</html>