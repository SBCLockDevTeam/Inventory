<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SBC Inventory Dashboard</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php
// Include the header template
include __DIR__ . '/../templates/common/header.php';

// Include the menu template
include __DIR__ . '/../templates/common/menu.php';

// Include error division template
include __DIR__ . '/../templates/common/error_division.php';

// Sample statistics
$statistics = [
    'Total Items' => 150,
    'Available Items' => 120,
    'Total Users' => 30,
];
?>

    <main class="container">
        <h2>Dashboard Statistics</h2>
        <div class="statistics">
            <?php foreach ($statistics as $key => $value): ?>
                <div class="stat-card">
                    <div class="stat-label"><?php echo htmlspecialchars($key); ?></div>
                    <div class="stat-value"><?php echo htmlspecialchars($value); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

<?php
// Include the footer template
include __DIR__ . '/../templates/common/footer.php';
?>
</body>
</html>
