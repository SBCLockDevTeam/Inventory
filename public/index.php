<?php
// public/index.php

// Include the header template
include __DIR__ . '/../templates/common/header.php';

// Include the menu template
include __DIR__ . '/../templates/common/menu.php';

// Include error division template
include __DIR__ . '/../templates/common/error_division.php';

// Get current date and time
$currentDateTime = date("Y-m-d H:i:s");

// Sample statistics (replace with dynamic data as needed)
$statistics = [
    'Total Items' => 150,
    'Available Items' => 120,
    'Total Users' => 30,
];

?>
    <main class="container">
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