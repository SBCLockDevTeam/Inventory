<?php
// public/index.php

// Get current date and time
$currentDateTime = date("Y-m-d H:i:s");

// Sample statistics (replace with dynamic data as needed)
$statistics = [
    'Total Items' => 150,
    'Available Items' => 120,
    'Total Users' => 30,
];

// Simple navigation
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
    <title>Main Dashboard</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
</head>
<body>
    <header>
        <h1>Dashboard</h1>
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
        <h2>Statistics</h2>
        <ul>
            <?php foreach ($statistics as $key => $value): ?>
                <li><?php echo $key . ': ' . $value; ?></li>
            <?php endforeach; ?>
        </ul>
    </main>
</body>
</html>
