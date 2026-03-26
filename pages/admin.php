<?php
declare(strict_types=1);

/**
 * Admin page – manage brands, users, and settings.
 *
 * Included by index.php after bootstrap has run.
 */

require_once __DIR__ . '/../lib/bootstrap.php';

$settings  = app_settings();
$pageTitle = 'Admin';

// Brand selector persistence.
if (isset($_GET['brand_id'])) {
    $_SESSION['brand_id'] = (int) $_GET['brand_id'];
}
$activeBrandId = $_SESSION['brand_id'] ?? null;

db     = db();
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
        <h1 class="page-title">Admin</h1>
        <p>Administration tools — coming soon.</p>
        <p><a href="<?= BASE_URL ?>/" class="btn btn-secondary">&larr; Back to Dashboard</a></p>
    </div>
</main>

<?php include __DIR__ . '/../templates/common/footer.php'; ?>
