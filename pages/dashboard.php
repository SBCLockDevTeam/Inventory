<?php
declare(strict_types=1);

/**
 * Dashboard page – the application home screen.
 *
 * Included by index.php after bootstrap has run.
 * $settings and BASE_URL are already available.
 */

require_once __DIR__ . '/../lib/bootstrap.php';

$settings  = app_settings();
$pageTitle = 'Dashboard – SBC Inventory';

// Brand selector: persist chosen brand in session.
// $_GET['brand_id'] overrides; session carries it across pages.
if (isset($_GET['brand_id'])) {
    $_SESSION['brand_id'] = (int) $_GET['brand_id'];
}
$activeBrandId = $_SESSION['brand_id'] ?? null;

// Load brands for the selector dropdown.
$db     = db();
$brands = $db->queryAll('SELECT id, name FROM brands ORDER BY name ASC');

// Load the active brand record (if one is selected).
$brand = null;
if ($activeBrandId !== null) {
    $brand = $db->queryOne('SELECT * FROM brands WHERE id = ?', [$activeBrandId]);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>

<?php include __DIR__ . '/../templates/common/header.php'; ?>
<?php include __DIR__ . '/../templates/common/menu.php'; ?>
<?php include __DIR__ . '/../templates/common/error_division.php'; ?>

<main class="main-content">
    <div class="container">
        <h1 class="page-title">Dashboard</h1>

        <?php if ($brand): ?>
            <p class="brand-welcome">Active brand: <strong><?= htmlspecialchars($brand['name']) ?></strong></p>
        <?php else: ?>
            <p class="brand-welcome">No brand selected. Use the brand selector in the top right to choose a brand.</p>
        <?php endif; ?>

        <section class="dashboard-cards">
            <a class="card" href="<?= BASE_URL ?>/item">
                <h2>Items</h2>
                <p>Browse and manage inventory items.</p>
            </a>
            <a class="card" href="<?= BASE_URL ?>/scan">
                <h2>Scan QR Code</h2>
                <p>Scan a QR code to look up an item.</p>
            </a>
            <a class="card" href="<?= BASE_URL ?>/admin">
                <h2>Admin</h2>
                <p>Manage brands, users, and settings.</p>
            </a>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../templates/common/footer.php'; ?>

</body>
</html>
