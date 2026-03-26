<?php
declare(strict_types=1);

/**
 * Item list / detail page.
 *
 * Included by index.php after bootstrap has run.
 * $settings and BASE_URL are already available.
 *
 * Routes:
 *   /item          – list all items (scoped to active brand if set)
 *   /item?id=N     – detail view for item with id=N
 */

require_once __DIR__ . '/../lib/bootstrap.php';

$settings  = app_settings();
$pageTitle = 'Items – SBC Inventory';

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

// If a specific item id is requested, show detail view.
$itemId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$item   = null;
if ($itemId !== null) {
    $item = $db->queryOne('SELECT * FROM items WHERE id = :id', [':id' => $itemId]);
}

// List items scoped to active brand when no specific item is requested.
$items = [];
if ($item === null) {
    if ($activeBrandId !== null) {
        $items = $db->queryAll(
            'SELECT * FROM items WHERE brand_id = :b ORDER BY name ASC',
            [':b' => $activeBrandId]
        );
    } else {
        $items = $db->queryAll('SELECT * FROM items ORDER BY name ASC');
    }
}

$menuItems = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/'],
    ['label' => 'Items', 'url' => BASE_URL . '/item'],
    ['label' => 'Admin', 'url' => BASE_URL . '/admin'],
    ['label' => 'Compliance', 'url' => BASE_URL . '/compliance'],
];

?>
<?php include __DIR__ . '/../templates/common/header.php'; ?>
<?php include __DIR__ . '/../templates/common/menu.php'; ?>
<?php include __DIR__ . '/../templates/common/error_division.php'; ?>

<main class="main-content">
    <div class="container">

        <?php if ($item !== null): ?>
            <h1 class="page-title"><?= htmlspecialchars($item['name'] ?? 'Item') ?></h1>
            <table class="detail-table">
                <tr><th>QR Code</th><td><?= htmlspecialchars($item['public_code'] ?? '') ?></td></tr>
                <tr><th>Name</th><td><?= htmlspecialchars($item['name'] ?? '') ?></td></tr>
                <tr><th>Description</th><td><?= htmlspecialchars($item['description'] ?? '') ?></td></tr>
            </table>
            <p>
                <a href="<?= BASE_URL ?>/item" class="btn btn-secondary">&larr; Back to Items</a>
                <a href="<?= BASE_URL ?>/"      class="btn btn-secondary">&larr; Dashboard</a>
            </p>

        <?php else: ?>
            <h1 class="page-title">Items</h1>

            <?php if (empty($items)): ?>
                <p>No items found<?= $activeBrandId ? ' for the selected brand' : '' ?>.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>QR Code</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $row): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($row['public_code'] ?? '') ?></code></td>
                            <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['description'] ?? '') ?></td>
                            <td><a href="<?= BASE_URL ?>/item?id=<?= (int) $row['id'] ?>">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <p><a href="<?= BASE_URL ?>/" class="btn btn-secondary">&larr; Back to Dashboard</a></p>
        <?php endif; ?>

    </div>
</main>

<?php include __DIR__ . '/../templates/common/footer.php'; ?>
