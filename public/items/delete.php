<?php
/**
 * Item Delete Page
 * Handles deletion of items with child reassignment warning and image cleanup.
 */

session_start();
require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/item_helpers.php';
require_once __DIR__ . '/../../lib/image_helpers.php';

$db = db();

$errors      = [];
$public_code = trim($_GET['id'] ?? '');

// ── Load item ────────────────────────────────────────────────────────────────
$item     = null;
$children = [];
if ($public_code !== '') {
    $item = getItemWithFields($db, $public_code);

    if (!$item) {
        $errors[] = 'Item not found.';
    } else {
        if ((int)$item['is_container']) {
            $children = getChildren($db, $public_code);
        }
    }
}

// ── Handle deletion confirmation ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete']) && $item) {
    $result = deleteItem($db, $public_code);

    if ($result['success']) {
        // Clean up the item's primary image from the filesystem
        if (!empty($result['image'])) {
            deleteImage($result['image']);
        }

        add_error('notice', 'Item deleted. ' . $result['children_moved'] . ' child item(s) moved to parent.');
        header('Location: /public/inventory.php?deleted=1&moved=' . (int)$result['children_moved']);
        exit;
    } else {
        $errors[] = $result['message'];
    }
}

$page_title = 'Delete Item';
?>
<?php include __DIR__ . '/../../templates/common/header.php'; ?>
<?php include __DIR__ . '/../../templates/common/menu.php'; ?>

<?php if (!empty($errors)): ?>
    <div class="error-banner">
        <?php foreach ($errors as $error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="container">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <?php if ($item): ?>
        <div class="delete-warning">
            <h2>⚠️ Confirm Deletion</h2>

            <p>You are about to permanently delete the following item:</p>

            <div class="item-details">
                <p><strong>Item Name:</strong> <?php echo htmlspecialchars($item['name']); ?></p>
                <p><strong>Public Code:</strong> <code><?php echo htmlspecialchars($item['public_code']); ?></code></p>
                <?php if (!empty($item['description'])): ?>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($item['description']); ?></p>
                <?php endif; ?>
                <?php if (!empty($item['primary_image'])): ?>
                    <p><strong>Image:</strong>
                        <img src="<?php echo htmlspecialchars($item['primary_image']); ?>"
                             alt="Item image" style="max-width:80px; vertical-align:middle;">
                        <em>(will also be deleted)</em>
                    </p>
                <?php endif; ?>
            </div>

            <?php if (!empty($children)): ?>
                <div class="children-warning">
                    <h3>⚠️ This item contains <?php echo count($children); ?> child item(s)</h3>
                    <p>The following items will be <strong>moved to this item's parent</strong> — they will NOT be deleted:</p>
                    <ul class="children-list">
                        <?php foreach ($children as $child): ?>
                            <li><?php echo htmlspecialchars($child['name']); ?>
                                (<code><?php echo htmlspecialchars($child['public_code']); ?></code>)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" class="delete-form">
                <input type="hidden" name="confirm_delete" value="1">

                <div class="form-actions">
                    <button type="submit" class="btn btn-danger">Yes, Delete This Item</button>
                    <a href="/public/items/view.php?id=<?php echo urlencode($public_code); ?>"
                       class="btn btn-secondary">Cancel</a>
                </div>
            </form>

            <p class="delete-note">
                <strong>Note:</strong> This action cannot be undone. All custom field data
                associated with this item will be permanently removed.
            </p>
        </div>

    <?php else: ?>
        <p>Item not found or no item specified.</p>
        <a href="/public/inventory.php" class="btn btn-primary">Back to Inventory</a>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../templates/common/footer.php'; ?>
</body>
</html>
