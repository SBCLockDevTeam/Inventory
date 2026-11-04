<?php
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/client_helper.php';

if (!ClientHelper::isActiveUserAdmin()) {
    header('Location: ' . BASE_PATH . '/home.php');
    exit;
}

$inspections = DatabaseHelper::queryAll(
    "SELECT fi.id, fi.inspection_date, fi.facility_name, fi.inspector_name, fi.notes, fi.created_at,
            COUNT(frr.id) AS item_count,
            SUM(CASE WHEN frr.condition_rating = 'critical' THEN 1 ELSE 0 END) AS critical_count
     FROM facility_review_inspections fi
     LEFT JOIN facility_review_results frr ON frr.inspection_id = fi.id
     GROUP BY fi.id, fi.inspection_date, fi.facility_name, fi.inspector_name, fi.notes, fi.created_at
     ORDER BY fi.inspection_date DESC, fi.id DESC",
    []
);

$page_title = 'Review History';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/table.css">
</head>
<body>
    <?php include __DIR__ . '/../../templates/common/header.php'; ?>
    <?php include __DIR__ . '/../../templates/common/menu.php'; ?>
    <div class="body-content">
        <h1>Review History</h1>
        <div class="items-table-wrapper">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Facility</th>
                        <th>Inspector</th>
                        <th>Items</th>
                        <th>Critical</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inspections)): ?>
                        <tr><td colspan="6" class="no-results">No inspection history found</td></tr>
                    <?php else: ?>
                        <?php foreach ($inspections as $inspection): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($inspection['inspection_date']); ?></td>
                                <td><?php echo htmlspecialchars($inspection['facility_name']); ?></td>
                                <td><?php echo htmlspecialchars($inspection['inspector_name']); ?></td>
                                <td><?php echo (int)$inspection['item_count']; ?></td>
                                <td><?php echo (int)$inspection['critical_count']; ?></td>
                                <td><?php echo htmlspecialchars($inspection['notes'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
</body>
</html>
