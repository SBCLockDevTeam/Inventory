<?php
/**
 * Item Delete Page
 * Handles deletion of items with child reassignment warning
 */

require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/item_helpers.php';

$errors = [];
$item_id = $_GET['id'] ?? '';

// Get item data
$item = null;
$children = [];
if ($item_id) {
    $item = getItemWithFields($db, $item_id);
    
    if (!$item) {
        $errors[] = 'Item not found';
    } else {
        // Get children if it's a container
        if ($item['is_container']) {
            $children = getChildren($db, $item_id);
        }
    }
}

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete']) && $item) {
    $result = deleteItem($db, $item_id);
    
    if ($result['success']) {
        // Redirect to inventory with success message
        header('Location: /public/inventory.php?deleted=1&moved=' . $result['children_moved']);
        exit;
    } else {
        $errors[] = $result['message'];
    }
}

$page_title = 'Delete Item';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - QR Inventory</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/layout.css">
    <link rel="stylesheet" href="/css/responsive.css">
</head>
<body>
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
                
                <p>You are about to delete the following item:</p>
                
                <div class="item-details">
                    <p><strong>Item Name:</strong> <?php echo htmlspecialchars($item['item_name']); ?></p>
                    <p><strong>Item ID:</strong> <?php echo htmlspecialchars($item['item_id']); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($item['item_description']); ?></p>
                </div>
                
                <?php if (!empty($children)): ?>
                    <div class="children-warning">
                        <h3>⚠️ This item contains <?php echo count($children); ?> child item(s)</h3>
                        
                        <p>The following items will be moved to this item's parent location:</p>
                        
                        <ul class="children-list">
                            <?php foreach ($children as $child): ?>
                                <li><?php echo htmlspecialchars($child['item_name']); ?> (ID: <?php echo htmlspecialchars($child['item_id']); ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <p class="warning-text">These child items will NOT be deleted. They will be reassigned to the parent container.</p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="delete-form">
                    <input type="hidden" name="confirm_delete" value="1">
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-danger">Yes, Delete This Item</button>
                        <a href="/public/items/view.php?id=<?php echo urlencode($item_id); ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
                
                <p class="delete-note">
                    <strong>Note:</strong> This action cannot be undone. All custom fields and data associated with this item will be permanently deleted.
                </p>
            </div>
        <?php else: ?>
            <p>Item not found.</p>
            <a href="/public/inventory.php" class="btn btn-primary">Back to Inventory</a>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
</body>
</html>
