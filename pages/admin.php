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
$pageTitle = 'Dashboard';

if (isset($_GET['brand_id'])) {
    $_SESSION['brand_id'] = (int) $_GET['brand_id'];
}
$activeBrandId = $_SESSION['brand_id'] ?? null;

$db     = db();
$brands = $db->queryAll('SELECT id, name FROM brands ORDER BY name ASC');

$brand = null;
if ($activeBrandId !== null) {
    $brand = $db->queryOne('SELECT * FROM brands WHERE id = :id', [':id' => $activeBrandId]);
}

?>
<?php include __DIR__ . '/../templates/common/header.php'; ?>
<?php include __DIR__ . '/../templates/common/menu.php'; ?>
<?php include __DIR__ . '/../templates/common/error_division.php'; ?>

<main class="main-content">
    <div class="container">
        <h1 class="page-title">Dashboard</h1>

        <?php if ($brand): ?>
            <p class="brand-welcome">Active brand: <strong><?= htmlspecialchars($brand['name']) ?></strong></p>
        <?php else: ?>
            <p class="brand-welcome">No brand selected. Use the brand selector to choose a brand.</p>
        <?php endif; ?>

        <section class="dashboard-cards">
            <a class="card" href="<?= BASE_URL ?>/item">
                <h2>Items</h2>
                <p>Browse and manage inventory items.</p>
            </a>
            <a class="card" href="<?= BASE_URL ?>/admin">
                <h2>Admin</h2>
                <p>Manage brands, users, and settings.</p>
            </a>
            <a class="card" href="<?= BASE_URL ?>/compliance">
                <h2>Compliance</h2>
                <p>View compliance reports and logs.</p>
            </a>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../templates/common/footer.php'; ?>
