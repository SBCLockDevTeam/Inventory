<?php
/**
 * View Item - shows item details, location breadcrumb, and children.
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';
require_once __DIR__ . '/../../lib/location_helper.php';

$item_id = FormHelper::getGet('id');
if (!FormHelper::isValidHex10($item_id)) {
    header('Location: ' . BASE_PATH . '/admin/items/');
    exit;
}

$item = DatabaseHelper::queryOne(
    "SELECT public_code, name, description, is_container,
            location_item_id, primary_image, created_at, updated_at
       FROM items
      WHERE public_code = ?",
    [$item_id]
);

if (!$item) {
    header('Location: ' . BASE_PATH . '/admin/items/');
    exit;
}

$breadcrumb = LocationHelper::getLocationBreadcrumb($item_id);
$children   = $item['is_container'] ? LocationHelper::getDirectChildren($item_id) : [];
$page_title = htmlspecialchars($item['name']);
$is_root    = ($item['location_item_id'] === $item['public_code']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> – View Item</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/table.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/location.css">
</head>
<body>
    <?php include __DIR__ . '/../../templates/common/header.php'; ?>
    <?php include __DIR__ . '/../../templates/common/menu.php'; ?>
    <div id="error-division" class="error-banner" style="display: none;"></div>

    <h1><?php echo $page_title; ?></h1>

    <div class="body-content">

        <!-- Location breadcrumb -->
        <?php if (!empty($breadcrumb)): ?>
        <nav class="location-breadcrumb" aria-label="Item location">
            <ul class="breadcrumb-list">
                <?php foreach ($breadcrumb as $index => $crumb):
                    $isLast = ($index === count($breadcrumb) - 1);
                ?>
                    <li>
                        <?php if ($isLast): ?>
                            <span class="breadcrumb-current <?php echo $crumb['is_container'] ? 'breadcrumb-container-icon' : 'breadcrumb-item-icon'; ?>">
                                <?php echo htmlspecialchars($crumb['name']); ?>
                            </span>
                        <?php else: ?>
                            <a class="<?php echo $crumb['is_container'] ? 'breadcrumb-container-icon' : 'breadcrumb-item-icon'; ?>"
                               href="<?php echo BASE_PATH; ?>/admin/items/view.php?id=<?php echo $crumb['public_code']; ?>">
                                <?php echo htmlspecialchars($crumb['name']); ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <!-- Action buttons -->
        <div class="item-actions">
            <a href="<?php echo BASE_PATH; ?>/admin/items/edit.php?id=<?php echo $item['public_code']; ?>" class="btn btn-primary">Edit Item</a>
            <a href="<?php echo BASE_PATH; ?>/admin/items/fields.php?id=<?php echo $item['public_code']; ?>" class="btn btn-secondary">Manage Fields</a>
            <a href="<?php echo BASE_PATH; ?>/admin/items/delete.php?id=<?php echo $item['public_code']; ?>" class="btn btn-danger">Delete Item</a>
            <a href="<?php echo BASE_PATH; ?>/admin/items/" class="btn btn-secondary">Back to Items</a>
        </div>

        <!-- Item details -->
        <div class="item-detail-card">
            <h2>Item Details</h2>
            <div class="item-detail-grid">
                <div class="item-detail-field">
                    <span class="field-label">Item ID</span>
                    <span class="field-value"><code><?php echo htmlspecialchars($item['public_code']); ?></code></span>
                </div>
                <div class="item-detail-field">
                    <span class="field-label">Name</span>
                    <span class="field-value"><?php echo htmlspecialchars($item['name']); ?></span>
                </div>
                <div class="item-detail-field">
                    <span class="field-label">Container</span>
                    <span class="field-value"><?php echo $item['is_container'] ? '✓ Yes' : 'No'; ?></span>
                </div>
                <div class="item-detail-field">
                    <span class="field-label">Location Type</span>
                    <span class="field-value"><?php echo $is_root ? 'Root item' : 'Child item'; ?></span>
                </div>
                <div class="item-detail-field">
                    <span class="field-label">Created</span>
                    <span class="field-value"><?php echo htmlspecialchars($item['created_at'] ?? '—'); ?></span>
                </div>
                <div class="item-detail-field" style="grid-column: 1 / -1;">
                    <span class="field-label">Description</span>
                    <span class="field-value"><?php echo nl2br(htmlspecialchars($item['description'] ?? '')); ?></span>
                </div>
            </div>
        </div>

        <!-- Children (only shown when this item is a container) -->
        <?php if ($item['is_container']): ?>
        <div class="children-section">
            <h2>Contents (<?php echo count($children); ?> item<?php echo count($children) !== 1 ? 's' : ''; ?>)</h2>
            <?php if (!empty($children)): ?>
            <table class="children-table">
                <thead>
                    <tr>
                        <th>Item ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($children as $child): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($child['public_code']); ?></code></td>
                        <td><?php echo htmlspecialchars($child['name']); ?></td>
                        <td>
                            <?php if ($child['is_container']): ?>
                                <span class="container-badge">Container</span>
                            <?php else: ?>
                                Item
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a href="<?php echo BASE_PATH; ?>/admin/items/view.php?id=<?php echo $child['public_code']; ?>" class="btn btn-small">View</a>
                            <a href="<?php echo BASE_PATH; ?>/admin/items/edit.php?id=<?php echo $child['public_code']; ?>" class="btn btn-small">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>This container is empty.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
