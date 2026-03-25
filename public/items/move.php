<?php
/**
 * Item Move Page
 * Allows moving items to different parent containers with circular reference prevention
 */

require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/item_helpers.php';

$db = db();

$errors = [];
$success = '';
$item_id = trim($_GET['id'] ?? '');

// Get item data
$item = null;
if ($item_id) {
    $item = queryOne($db, "SELECT * FROM items WHERE public_code = ?", [$item_id]);
    
    if (!$item) {
        $errors[] = 'Item not found';
    } elseif (isRootItem($db, $item_id)) {
        $errors[] = 'Root items cannot be moved';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $item && !isRootItem($db, $item_id)) {
    $new_parent_id = trim($_POST['location_item_id'] ?? '');
    
    if (empty($new_parent_id)) {
        $errors[] = 'Please select a new parent location';
    } else {
        // Check if new parent exists and is a container
        $new_parent = queryOne($db, "SELECT * FROM items WHERE public_code = ?", [$new_parent_id]);
        
        if (!$new_parent) {
            $errors[] = 'Selected parent location does not exist';
        } elseif (!$new_parent['is_container']) {
            $errors[] = 'Selected location is not a container';
        } elseif (wouldCreateCircularReference($db, $item_id, $new_parent_id)) {
            $errors[] = 'Cannot move item: this would create a circular reference. An item cannot be moved into one of its own descendants.';
        } else {
            try {
                execute($db,
                    "UPDATE items SET location_item_id = ?, updated_at = NOW() WHERE public_code = ?",
                    [$new_parent_id, $item_id]
                );
                
                logActivity("Item moved: {$item['name']} (Code: {$item_id}) to {$new_parent['name']} (Code: {$new_parent_id})");
                
                // Redirect to item view with success message
                header("Location: /public/items/view.php?id={$item_id}&moved=1");
                exit;
                
            } catch (Exception $e) {
                $errors[] = 'Error moving item: ' . $e->getMessage();
            }
        }
    }
}

// Get all potential parent items (containers) excluding the item itself and its descendants
$all_containers = queryAll($db, "SELECT public_code, name, location_item_id FROM items WHERE is_container = 1 ORDER BY name");

// Filter out the item itself and its descendants
$descendants = $item ? getDescendants($db, $item_id) : [];
$descendants[] = $item_id; // Add the item itself

$available_containers = array_filter($all_containers, function($container) use ($descendants) {
    return !in_array($container['public_code'], $descendants);
});

$page_title = 'Move Item';
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
        
        <?php if ($item && !isRootItem($db, $item_id)): ?>
            <div class="item-info">
                <h2>Moving Item:</h2>
                <p><strong><?php echo htmlspecialchars($item['name']); ?></strong></p>
                <p><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                
                <?php
                $current_parent = queryOne($db, "SELECT name FROM items WHERE public_code = ?", [$item['location_item_id']]);
                ?>
                <p><em>Current location: <?php echo htmlspecialchars($current_parent['name'] ?? 'Unknown'); ?></em></p>
            </div>
            
            <form method="POST" class="move-form">
                <div class="form-section">
                    <h2>Select New Parent Location</h2>
                    
                    <div class="form-group">
                        <label for="location_item_id">New Parent Container *</label>
                        <select id="location_item_id" name="location_item_id" required>
                            <option value="">-- Select New Location --</option>
                            <?php foreach ($available_containers as $container): ?>
                                <option value="<?php echo htmlspecialchars($container['public_code']); ?>" 
                                        <?php echo $container['public_code'] === $item['location_item_id'] ? 'selected' : ''; ?> >
                                    <?php echo htmlspecialchars($container['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="help-text">Only container items are shown. Items that would create circular references are excluded.</p>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Move Item</button>
                    <a href="/public/items/view.php?id=<?php echo urlencode($item_id); ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
            
            <div class="move-info">
                <h3>About Moving Items</h3>
                <ul>
                    <li>✅ The item will be moved to the selected parent container</li>
                    <li>✅ If this item is a container, all its child items will move with it</li>
                    <li>✅ Circular references are automatically prevented</li>
                    <li>❌ Root items cannot be moved</li>
                    <li>❌ Items cannot be moved into themselves or their descendants</li>
                </ul>
            </div>
        <?php elseif ($item && isRootItem($db, $item_id)): ?>
            <p class="error">Root items cannot be moved. Only administrators can modify root items.</p>
            <a href="/public/items/view.php?id=<?php echo urlencode($item_id); ?>" class="btn btn-primary">Back to Item</a>
        <?php else: ?>
            <p>Item not found.</p>
            <a href="/public/inventory.php" class="btn btn-primary">Back to Inventory</a>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
    
    <script src="/js/lib/form_helpers.js"></script>
</body>
</html>