<?php
require __DIR__ . '/../config/secrets.php';
require __DIR__ . '/../lib/database.php';

$db = new Database();
$currentDateTime = date("Y-m-d H:i:s");

// Get statistics
$db->query("SELECT COUNT(*) as total FROM items");
$totalItems = $db->queryOne()['total'];

$db->query("SELECT COUNT(*) as total FROM items WHERE is_container = 0");
$availableItems = $db->queryOne()['total'];

$db->query("SELECT COUNT(*) as total FROM brands");
$totalBrands = $db->queryOne()['total'];

$db->query("SELECT COUNT(*) as total FROM general_log");
$totalLogEntries = $db->queryOne()['total'];

$statistics = [
    'Total Items' => $totalItems,
    'Available Items (Non-Containers)' => $availableItems,
    'Total Brands' => $totalBrands,
    'Log Entries' => $totalLogEntries,
];

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
    <title>Statistics</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Statistics</h1>
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
        <h2>System Statistics</h2>
        <ul>
            <?php foreach ($statistics as $key => $value): ?>
                <li><?php echo $key . ': ' . $value; ?></li>
            <?php endforeach; ?>
        </ul>
    </main>
</body>
</html>
