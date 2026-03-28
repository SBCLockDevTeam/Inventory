<?php
/**
 * Exceptions Log - customer-facing simplified log.
 * Shows plain-language event summaries. No before/after values.
 * Available to all users (not admin-only).
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';
require_once __DIR__ . '/../../lib/client_helper.php';

// Admin only
if (!ClientHelper::isActiveUserAdmin()) {
    header('Location: ' . BASE_PATH . '/');
    exit;
}

$per_page    = 50;
$page        = max(1, (int)FormHelper::getGet('page', '1'));
$offset      = ($page - 1) * $per_page;
$filter_item = FormHelper::getGet('item_code');

$where  = [];
$params = [];

if (!empty($filter_item)) {
    $where[]  = "e.item_public_code = ?";
    $params[] = $filter_item;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count_params = $params;
$total = DatabaseHelper::queryOne(
    "SELECT COUNT(*) AS c FROM exceptions_log e $where_sql",
    $count_params
);
$total_rows  = (int)($total['c'] ?? 0);
$total_pages = max(1, (int)ceil($total_rows / $per_page));

$limit_params = array_merge($params, [$per_page, $offset]);
$logs = DatabaseHelper::queryAll(
    "SELECT e.id, e.item_public_code, e.event_summary, e.created_at
       FROM exceptions_log e
      $where_sql
      ORDER BY e.created_at DESC
      LIMIT ? OFFSET ?",
    $limit_params
);

$page_title = 'Exceptions Log';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/table.css">
    <script src="<?php echo JS_PATH; ?>script.js" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../templates/common/header.php'; ?>
    <?php include __DIR__ . '/../../templates/common/menu.php'; ?>
    <div id="error-division" class="error-banner" style="display: none;"></div>
    <h1>Exceptions Log</h1>
    <div class="body-content">

        <p style="color:#7f8c8d;margin-bottom:1rem;">
            This log shows significant events in plain language.
            For full technical details, see the
            <a href="<?php echo BASE_PATH; ?>/admin/logs/">General Log</a>.
        </p>

        <!-- Filter by item -->
        <form method="GET" action="" class="filter-form" style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-bottom:1rem;">
            <div class="form-group" style="margin:0;">
                <input type="text" name="item_code" placeholder="Filter by Item ID"
                       value="<?php echo htmlspecialchars($filter_item); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="<?php echo BASE_PATH; ?>/admin/logs/exceptions.php" class="btn btn-secondary">Clear</a>
        </form>

        <p style="color:#7f8c8d;font-size:0.875rem;"><?php echo number_format($total_rows); ?> event<?php echo $total_rows !== 1 ? 's' : ''; ?> found.</p>

        <div class="items-table-wrapper">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Date / Time</th>
                        <th>Item</th>
                        <th>Event</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td style="white-space:nowrap;font-size:0.85rem;"><?php echo htmlspecialchars($log['created_at']); ?></td>
                            <td>
                                <?php if ($log['item_public_code']): ?>
                                    <a href="<?php echo BASE_PATH; ?>/admin/items/view.php?id=<?php echo htmlspecialchars($log['item_public_code']); ?>">
                                        <?php echo htmlspecialchars($log['item_public_code']); ?>
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($log['event_summary']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="no-results">No events recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="actions-bottom" style="justify-content:center;flex-wrap:wrap;">
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <?php $qs = http_build_query(['item_code' => $filter_item, 'page' => $p]); ?>
                <a href="?<?php echo $qs; ?>"
                   class="btn btn-small <?php echo ($p === $page) ? 'btn-primary' : 'btn-secondary'; ?>">
                    <?php echo $p; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
