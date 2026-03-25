<?php
/**
 * View Item Page
 * Displays all item details, custom fields, child items, and action buttons.
 */

session_start();
require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/item_helpers.php';
require_once __DIR__ . '/../../lib/field_helpers.php';

$db = db();

$public_code = trim($_GET['id'] ?? '');

if ($public_code === '') {
    http_response_code(404);
    add_error('error', 'No item specified.');
    header('Location: /public/inventory.php');
    exit;
}

$item = getItemWithFields($db, $public_code);

if (!$item) {
    http_response_code(404);
    add_error('error', 'Item not found.');
    header('Location: /public/inventory.php');
    exit;
}

// Breadcrumb trail
$breadcrumb = getItemBreadcrumb($db, $public_code);

// Children (only if container)
$children = (int)$item['is_container'] ? getChildren($db, $public_code) : [];

// Field values map
$field_values = getItemFieldValues($db, $public_code);

// Flash messages from redirects
$created = !empty($_GET['created']);
$updated = !empty($_GET['updated']);
$moved   = !empty($_GET['moved']);

$page_title = htmlspecialchars($item['name']);
?>
<?php include __DIR__ . '/../../templates/common/header.php'; ?>
<?php include __DIR__ . '/../../templates/common/menu.php'; ?>

<div class="container">

    <?php if ($created): ?>
        <div class="info-banner success-banner"><p>✓ Item created successfully.</p></div>
    <?php elseif ($updated): ?>
        <div class="info-banner success-banner"><p>✓ Item updated successfully.</p></div>
    <?php elseif ($moved): ?>
        <div class="info-banner success-banner"><p>✓ Item moved successfully.</p></div>
    <?php endif; ?>

    <!-- Breadcrumb -->
    <?php if (!empty($breadcrumb)): ?>
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <?php foreach ($breadcrumb as $i => $crumb): ?>
                <?php if ($i > 0): ?> &rsaquo; <?php endif; ?>
                <?php if ($crumb['public_code'] !== $public_code): ?>
                    <a href="view.php?id=<?php echo urlencode($crumb['public_code']); ?>">
                        <?php echo htmlspecialchars($crumb['name']); ?>
                    </a>
                <?php else: ?>
                    <span><?php echo htmlspecialchars($crumb['name']); ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <h1><?php echo htmlspecialchars($item['name']); ?></h1>

    <div class="item-view-layout">

        <!-- ── Left Column: Image ──────────────────────────────── -->
        <div class="item-image-col">
            <?php if (!empty($item['primary_image'])): ?>
                <img src="<?php echo htmlspecialchars($item['primary_image']); ?>"
                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                     class="item-image-large">
            <?php else: ?>
                <div class="item-image-placeholder">
                    <span>No Image</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── Right Column: Details ───────────────────────────── -->
        <div class="item-details-col">
            <table class="detail-table">
                <tr>
                    <th>Public Code</th>
                    <td><code><?php echo htmlspecialchars($item['public_code']); ?></code></td>
                </tr>
                <tr>
                    <th>Brand</th>
                    <td><?php echo htmlspecialchars($item['brand_name'] ?? 'N/A'); ?></td>
                </tr>
                <?php if (!empty($item['description'])): ?>
                <tr>
                    <th>Description</th>
                    <td><?php echo nl2br(htmlspecialchars($item['description'])); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th>Type</th>
                    <td><?php echo (int)$item['is_container'] ? 'Container' : 'Item'; ?></td>
                </tr>
                <?php if (!isRootItem($db, $public_code)):
                    $parent = queryOne($db,
                        'SELECT public_code, name FROM items WHERE public_code = ?',
                        [$item['location_item_id']]); ?>
                <tr>
                    <th>Location</th>
                    <td>
                        <?php if ($parent): ?>
                            <a href="view.php?id=<?php echo urlencode($parent['public_code']); ?>">
                                <?php echo htmlspecialchars($parent['name']); ?>
                            </a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($item['location_item_id']); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th>Created</th>
                    <td><?php echo htmlspecialchars($item['created_at']); ?></td>
                </tr>
                <?php if (!empty($item['updated_at'])): ?>
                <tr>
                    <th>Last Updated</th>
                    <td><?php echo htmlspecialchars($item['updated_at']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- ── Custom Fields ──────────────────────────────────────── -->
    <?php if (!empty($item['fields'])): ?>
    <section class="item-section">
        <h2>Custom Fields</h2>
        <table class="detail-table">
            <?php foreach ($item['fields'] as $field_def):
                $fid     = (int)$field_def['id'];
                $val_row = $field_values[$fid] ?? null;
                $display = $val_row ? getFieldDisplayValue($val_row) : '';
            ?>
            <tr>
                <th><?php echo htmlspecialchars($field_def['label']); ?></th>
                <td><?php if ($display !== ''): echo htmlspecialchars($display); else: ?><em>&#8212;</em><?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </section>
    <?php endif; ?>

    <!-- ── Child Items (containers only) ──────────────────────── -->
    <?php if ((int)$item['is_container'] && !empty($children)): ?>
    <section class="item-section">
        <h2>Contents (<?php echo count($children); ?> item<?php echo count($children) !== 1 ? 's' : ''; ?>)</h2>
        <ul class="children-list">
            <?php foreach ($children as $child): ?>
                <li>
                    <a href="view.php?id=<?php echo urlencode($child['public_code']); ?>">
                        <?php echo htmlspecialchars($child['name']); ?>
                    </a>
                    <?php if ((int)$child['is_container']): ?>
                        <span class="badge">Container</span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php elseif ((int)$item['is_container']): ?>
    <section class="item-section">
        <h2>Contents</h2>
        <p>This container is empty.</p>
    </section>
    <?php endif; ?>

    <!-- ── Actions ────────────────────────────────────────────── -->
    <section class="item-section">
        <h2>Actions</h2>
        <div class="form-actions">
            <a href="edit.php?id=<?php echo urlencode($public_code); ?>" class="btn btn-primary">✏️ Edit</a>
            <a href="add.php?clone=<?php echo urlencode($public_code); ?>" class="btn btn-secondary">📋 Clone</a>
            <?php if (!isRootItem($db, $public_code)): ?>
                <a href="move.php?id=<?php echo urlencode($public_code); ?>" class="btn btn-secondary">📦 Move</a>
                <a href="delete.php?id=<?php echo urlencode($public_code); ?>" class="btn btn-danger">🗑️ Delete</a>
            <?php endif; ?>
            <a href="/public/inventory.php" class="btn btn-secondary">← Back to Inventory</a>
        </div>
    </section>
</div>

<?php include __DIR__ . '/../../templates/common/footer.php'; ?>
</body>
</html>
