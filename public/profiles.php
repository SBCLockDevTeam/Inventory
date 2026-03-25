<?php
require __DIR__ . '/../config/secrets.php';
require __DIR__ . '/../lib/database.php';

$db = new Database();
$currentDateTime = date("Y-m-d H:i:s");

// Get brands (as profiles for now)
$db->query("SELECT * FROM brands ORDER BY created_at DESC");
$profiles = $db->queryAll();

$navigation = [
    'Home' => 'index.php',
    'Inventory' => 'inventory.php',
    'Statistics' => 'statistics.php',
    'Profiles' => 'profiles.php',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profiles</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Profiles</h1>
        <p>Current Date and Time (UTC): <?php echo $currentDateTime; ?></p>
    </header>
    <nav>
        <ul>
            <?php foreach ($navigation as $name => $link): ?>
                <li><a href="<?php echo $link; ?>"><?php echo $name; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <main>
        <h2>Brands</h2>
        <?php if ($profiles): ?>
            <table border="1" cellpadding="10" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Default</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($profiles as $profile): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($profile['id']); ?></td>
                            <td><?php echo htmlspecialchars($profile['name']); ?></td>
                            <td><?php echo htmlspecialchars($profile['description'] ?? 'N/A'); ?></td>
                            <td><?php echo $profile['is_default'] ? 'Yes' : 'No'; ?></td>
                            <td><?php echo $profile['created_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No profiles found.</p>
        <?php endif; ?>
    </main>
</body>
</html>
