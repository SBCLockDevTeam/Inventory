<?php
require __DIR__ . '/../config/secrets.php';
require __DIR__ . '/../lib/database.php';

$db = new Database();
$currentDateTime = date("Y-m-d H:i:s");

// Get all items
$db->query("SELECT * FROM items ORDER BY created_at DESC");
$items = $db->queryAll();

$page_title = 'Inventory';
?>
<?php include __DIR__ . '/../templates/common/header.php'; ?>
<?php include __DIR__ . '/../templates/common/menu.php'; ?>

<div class="container">
    <h1>Inventory</h1>
    <p>Current Date and Time (UTC): <?php echo $currentDateTime; ?></p>
    
    <div class="form-actions">
        <a href="/qr/public/items/add.php" class="btn btn-primary">➕ Add New Item</a>
    </div>

    <h2>Items</h2>
    <?php if ($items): ?>
        <table border="1" cellpadding="10" cellspacing="0">
            <thead>
                <tr>
                    <th>Public Code</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Is Container</th>
                    <th>Location</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <a href="/qr/public/items/view.php?id=<?php echo urlencode($item['public_code']); ?>"><?php echo htmlspecialchars($item['public_code']); ?></a>
                        </td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></td>
                        <td><?php echo $item['is_container'] ? 'Yes' : 'No'; ?></td>
                        <td><?php echo htmlspecialchars($item['location_item_id']); ?></td>
                        <td><?php echo $item['created_at']; ?></td>
                        <td>
                            <a href="/qr/public/items/view.php?id=<?php echo urlencode($item['public_code']); ?>">View</a> |
                            <a href="/qr/public/items/edit.php?id=<?php echo urlencode($item['public_code']); ?>">Edit</a> |
                            <a href="/qr/public/items/delete.php?id=<?php echo urlencode($item['public_code']); ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No items found.</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../templates/common/footer.php'; ?>
</body>
</html>
