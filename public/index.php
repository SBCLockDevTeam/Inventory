<?php
// Dashboard entry page for /qr/
// Uses shared templates and pulls real stats from the database.

declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

$stats = [
    'Total Items' => 0,
    'Containers' => 0,
    'Brands' => 0,
];

try {
    $db = db();

    // Total items
    $db->query('SELECT COUNT(*) AS c FROM items');
    $row = $db->queryOne();
    $stats['Total Items'] = (int)($row['c'] ?? 0);

    // Containers
    $db->query('SELECT COUNT(*) AS c FROM items WHERE is_container = 1');
    $row = $db->queryOne();
    $stats['Containers'] = (int)($row['c'] ?? 0);

    // Brands
    $db->query('SELECT COUNT(*) AS c FROM brands');
    $row = $db->queryOne();
    $stats['Brands'] = (int)($row['c'] ?? 0);
} catch (Throwable $e) {
    // Don't expose internals; show a friendly message in the error division.
    add_error('error', 'Dashboard failed to load stats. Verify DB connection and run /qr/install/setup.php.');
    log_exception($e, 'dashboard_stats');
}

include __DIR__ . '/../templates/common/header.php';
include __DIR__ . '/../templates/common/menu.php';
include __DIR__ . '/../templates/common/error_division.php';
?>

<main class="container">
    <h2>Dashboard Statistics</h2>
    <div class="statistics">
        <?php foreach ($stats as $key => $value): ?>
            <div class="stat-card">
                <div class="stat-label"><?php echo htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="stat-value"><?php echo htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php
include __DIR__ . '/../templates/common/footer.php';