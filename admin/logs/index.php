<?php
/**
 * General Log - admin-facing detailed activity log.
 * Shows recent events with timestamps, user, action type, item, and before/after values.
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';
require_once __DIR__ . '/../../lib/client_helper.php';

// Admin only
if (!ClientHelper::isActiveUserAdmin()) {
    header('Location: ' . BASE_PATH . '/home.php');
    exit;
}

$per_page    = 50;
$page        = max(1, (int)FormHelper::getGet('page', '1'));
$offset      = ($page - 1) * $per_page;
$filter_item = FormHelper::getGet('item_code');
$filter_type = FormHelper::getGet('action_type');

$where  = [];
$params = [];

if (!empty($filter_item)) {
    $where[]  = "item_public_code = ?";
    $params[] = $filter_item;
}
if (!empty($filter_type)) {
    $where[]  = "action_type = ?";
    $params[] = $filter_type;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = DatabaseHelper::queryOne(
    "SELECT COUNT(*) AS c FROM general_log $where_sql",
    $params
);
$total_rows  = (int)($total['c'] ?? 0);
$total_pages = max(1, (int)ceil($total_rows / $per_page));

$limit_params = array_merge($params, [$per_page, $offset]);
$logs = DatabaseHelper::queryAll(
    "SELECT gl.id, gl.user_identifier, gl.action_type, gl.item_public_code,
            gl.field_id, gl.value_before, gl.value_after, gl.notes, gl.created_at,
            i.name AS item_name
       FROM general_log gl
       LEFT JOIN items i ON i.public_code = gl.item_public_code
      $where_sql
      ORDER BY gl.created_at DESC
      LIMIT ? OFFSET ?",
    $limit_params
);

// Distinct action types for filter dropdown
$action_types = DatabaseHelper::queryAll(
    "SELECT DISTINCT action_type FROM general_log ORDER BY action_type",
    []
);

$page_title = 'General Log';
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
    <div class="body-content">

        <!-- Filters -->
        <form method="GET" action="" class="filter-form" style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-bottom:1rem;">
            <input type="hidden" name="item_code" value="<?php echo htmlspecialchars($filter_item); ?>">
            <div class="form-group" style="margin:0;">
                <select name="action_type">
                    <option value="">— All action types —</option>
                    <?php foreach ($action_types as $at):
                        $safe_type = htmlspecialchars($at['action_type']);
                    ?>
                        <option value="<?php echo $safe_type; ?>"
                                <?php echo ($filter_type === $at['action_type']) ? 'selected' : ''; ?>>
                            <?php echo $safe_type; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="<?php echo BASE_PATH; ?>/admin/logs/" class="btn btn-secondary">Clear</a>
        </form>

        <p style="color:#7f8c8d;font-size:0.875rem;"><?php echo number_format($total_rows); ?> record<?php echo $total_rows !== 1 ? 's' : ''; ?> found. Showing page <?php echo $page; ?> of <?php echo $total_pages; ?>.</p>

        <div class="items-table-wrapper">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Item</th>
                        <th>Notes / Before / After</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td style="white-space:nowrap;font-size:0.85rem;"><?php echo htmlspecialchars($log['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($log['user_identifier'] ?? '—'); ?></td>
                            <td><code><?php echo htmlspecialchars($log['action_type']); ?></code></td>
                            <td>
                                <?php if ($log['item_public_code']): ?>
                                    <a href="<?php echo BASE_PATH; ?>/items/view.php?id=<?php echo htmlspecialchars($log['item_public_code']); ?>">
                                        <?php echo htmlspecialchars($log['item_name'] ?? '(Deleted Item)'); ?>
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.85rem;">
                                <?php if ($log['notes']): ?>
                                    <strong>Note:</strong> <?php echo htmlspecialchars($log['notes']); ?><br>
                                <?php endif; ?>
                                <?php if ($log['value_before'] !== null): ?>
                                    <strong>Before:</strong> <?php echo htmlspecialchars($log['value_before']); ?><br>
                                <?php endif; ?>
                                <?php if ($log['value_after'] !== null): ?>
                                    <strong>After:</strong> <?php echo htmlspecialchars($log['value_after']); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="no-results">No log entries found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="actions-bottom" style="justify-content:center;flex-wrap:wrap;">
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <?php $qs = http_build_query(['item_code' => $filter_item, 'action_type' => $filter_type, 'page' => $p]); ?>
                <a href="?<?php echo $qs; ?>"
                   class="btn btn-small <?php echo ($p === $page) ? 'btn-primary' : 'btn-secondary'; ?>">
                    <?php echo $p; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
