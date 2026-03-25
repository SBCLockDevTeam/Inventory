<?php
require __DIR__ . '/../config/secrets.php';
require __DIR__ . '/../lib/database.php';

db = new Database();
$currentDateTime = date("Y-m-d H:i:s");

// Get all items
db->query("SELECT * FROM items ORDER BY created_at DESC");
$items = db->queryAll();

$navigation = [
    'Home' => '/qr/public/index.php',
    'Inventory' => '/qr/public/inventory.php',
    'Statistics' => '/qr/public/statistics.php',
    'Profiles' => '/qr/public/profiles.php',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory</title>
    <link rel="stylesheet" href="/qr/public/styles.css">
</head>
<body>
    <header>
        <h1>Inventory</h1>
        <p>Current Date and Time (UTC): <?php echo $currentDateTime; ?></p>
    </header>
    <nav>
        <ul>
            <?php foreach ($navigation as $name => $link): ?>
                <li><a href="<?php echo $link; "><?php echo $name; ?></a></li>
            <?php endforeach; ?>
        </ul>
        <p><a href="/qr/public/items/add.php">➕ Add New Item</a></p>
    </nav>
    <main>
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
                                <a href="/qr/public/items/view.php?id=<?php echo urlencode($item['public_code']); ?>>View</a> |
                                <a href="/qr/public/items/edit.php?id=<?php echo urlencode($item['public_code']); ?>>Edit</a> |
                                <a href="/qr/public/items/delete.php?id=<?php echo urlencode($item['public_code']); ?>>Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No items found.</p>
        <?php endif; ?>
    </main>
</body>
</html>