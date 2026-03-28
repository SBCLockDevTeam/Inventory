<?php
/**
 * Delete Item - confirmation page with child-item handling.
 * When a container has children, they are moved to the container's parent
 * before the container is deleted (children are never orphaned).
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/form_helpers.php';
require_once __DIR__ . '/../lib/location_helper.php';

$item_id = FormHelper::getGet('id');
if (!FormHelper::isValidHex10($item_id)) {
    header('Location: ' . BASE_PATH . '/items/');
    exit;
}

$item = DatabaseHelper::queryOne(
    "SELECT public_code, name, description, is_container, location_item_id
       FROM items
      WHERE public_code = ?",
    [$item_id]
);

if (!$item) {
    header('Location: ' . BASE_PATH . '/items/');
    exit;
}

$children    = LocationHelper::getDirectChildren($item_id);
$has_children = !empty($children);
$is_root      = ($item['location_item_id'] === $item['public_code']);

$errors  = [];
$deleted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {

    DatabaseHelper::beginTransaction();

    try {
        // Reparent children to this item's parent before deletion
        if ($has_children) {
            if ($is_root) {
                // Children of a root item become root items (own parent)
                foreach ($children as $child) {
                    DatabaseHelper::execute(
                        "UPDATE items SET location_item_id = public_code WHERE public_code = ?",
                        [$child['public_code']]
                    );
                }
            } else {
                // Move all direct children up to the grandparent in one query
                DatabaseHelper::execute(
                    "UPDATE items SET location_item_id = ? WHERE location_item_id = ? AND public_code != ?",
                    [$item['location_item_id'], $item_id, $item_id]
                );
            }
        }

        // Delete the item (ON DELETE CASCADE removes item_fields, values, images, etc.)
        $affected = DatabaseHelper::execute(
            "DELETE FROM items WHERE public_code = ?",
            [$item_id]
        );

        if ($affected > 0) {
            DatabaseHelper::commit();
            $deleted = true;
        } else {
            DatabaseHelper::rollback();
            $errors[] = 'Delete failed: ' . DatabaseHelper::getLastError();
        }
    } catch (Exception $e) {
        DatabaseHelper::rollback();
        $errors[] = 'Delete failed: ' . $e->getMessage();
    }
}

if ($deleted) {
    header('Location: ' . BASE_PATH . '/items/');
    exit;
}

$breadcrumb = LocationHelper::getLocationBreadcrumb($item_id);
$page_title = 'Delete Item – ' . htmlspecialchars($item['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/location.css">
    <script src="<?php echo JS_PATH; ?>script.js" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../templates/common/header.php'; ?>
    <?php include __DIR__ . '/../templates/common/menu.php'; ?>
    <div id="error-division" class="error-banner" style="display: <?php echo !empty($errors) ? 'block' : 'none'; ?>;">
        <?php foreach ($errors as $error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>

    <div class="body-content">

        <!-- Location breadcrumb -->
        <?php if (!empty($breadcrumb)): ?>
        <nav class="location-breadcrumb" aria-label="Item location">
            <ul class="breadcrumb-list">
                <?php foreach ($breadcrumb as $crumb): ?>
                    <li>
                        <a class="<?php echo $crumb['is_container'] ? 'breadcrumb-container-icon' : 'breadcrumb-item-icon'; ?>"
                           href="<?php echo BASE_PATH; ?>/items/view.php?id=<?php echo $crumb['public_code']; ?>">
                            <?php echo htmlspecialchars($crumb['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <div class="delete-confirm-card">

            <!-- Child items warning -->
            <?php if ($has_children): ?>
            <div class="warning-block">
                <strong>⚠ This item contains <?php echo count($children); ?> child item<?php echo count($children) !== 1 ? 's' : ''; ?>.</strong>
                <p style="margin-top:0.5rem;">
                    <?php if ($is_root): ?>
                        Because this is a root item, each child will become its own root item after deletion.
                    <?php else: ?>
                        All child items will be moved up to the parent location
                        before this item is deleted.
                    <?php endif; ?>
                </p>
                <ul style="margin-top:0.5rem; padding-left:1.5rem;">
                    <?php foreach ($children as $child): ?>
                        <li><?php echo htmlspecialchars($child['name']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Deletion warning -->
            <div class="danger-block">
                <strong>🗑 You are about to permanently delete:</strong>
                <p style="margin-top:0.5rem;">
                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                </p>
                <p style="margin-top:0.5rem;">This action cannot be undone. All custom fields and data for this item will also be deleted.</p>
            </div>

            <!-- Confirm/Cancel buttons -->
            <form method="POST" action="">
                <div class="form-actions">
                    <button type="submit" name="confirm_delete" value="1" class="btn btn-danger">Yes, Delete Item</button>
                    <a href="<?php echo BASE_PATH; ?>/items/view.php?id=<?php echo $item['public_code']; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>

        </div>
    </div>
    <?php include __DIR__ . '/../templates/common/footer.php'; ?>
