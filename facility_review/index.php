<?php
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/client_helper.php';

if (!ClientHelper::getActiveUser()) {
    header('Location: ' . BASE_PATH . '/auth/login.php');
    exit;
}

$inspection_date = date('Y-m-d');
$facility_name   = '';
$inspector_name  = ClientHelper::getActiveUser()['name'] ?? '';
$notes           = '';
$errors          = [];
$success         = false;

$categories = DatabaseHelper::queryAll(
    "SELECT id, name, description, sort_order FROM facility_review_categories ORDER BY sort_order, name",
    []
);

$itemsByCategory = [];
foreach ($categories as $category) {
    $items = DatabaseHelper::queryAll(
        "SELECT id, code, name, description, default_severity FROM facility_review_items WHERE category_id = ? AND is_active = 1 ORDER BY sort_order, name",
        [(int)$category['id']]
    );
    $itemsByCategory[(int)$category['id']] = $items;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inspection_date = trim((string)($_POST['inspection_date'] ?? $inspection_date));
    $facility_name   = trim((string)($_POST['facility_name'] ?? ''));
    $inspector_name  = trim((string)($_POST['inspector_name'] ?? $inspector_name));
    $notes           = trim((string)($_POST['notes'] ?? ''));

    if ($inspection_date === '') {
        $errors[] = 'Inspection date is required.';
    }
    if ($facility_name === '') {
        $errors[] = 'Facility name is required.';
    }
    if ($inspector_name === '') {
        $errors[] = 'Inspector name is required.';
    }

    $submission = [];
    foreach ($categories as $category) {
        foreach ($itemsByCategory[(int)$category['id']] as $item) {
            $ratingKey = 'rating_' . (int)$item['id'];
            $noteKey   = 'notes_' . (int)$item['id'];
            $rating    = trim((string)($_POST[$ratingKey] ?? ''));
            $itemNotes = trim((string)($_POST[$noteKey] ?? ''));

            if ($rating === '') {
                $errors[] = 'Please select a condition rating for ' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');
            }

            $submission[] = [
                'id'    => (int)$item['id'],
                'rating' => $rating,
                'notes' => $itemNotes,
            ];
        }
    }

    if (empty($errors)) {
        DatabaseHelper::beginTransaction();
        try {
            DatabaseHelper::execute(
                "INSERT INTO facility_review_inspections (inspection_date, facility_name, inspector_name, notes, created_by) VALUES (?, ?, ?, ?, ?)",
                [$inspection_date, $facility_name, $inspector_name, $notes, ClientHelper::getActiveUser()['id'] ?? null]
            );

            $inspectionId = (int)DatabaseHelper::getLastInsertId();

            foreach ($submission as $entry) {
                DatabaseHelper::execute(
                    "INSERT INTO facility_review_results (inspection_id, item_id, condition_rating, notes) VALUES (?, ?, ?, ?)",
                    [$inspectionId, $entry['id'], $entry['rating'], $entry['notes']]
                );
            }

            DatabaseHelper::commit();
            $success = true;
            $notes   = '';
        } catch (Exception $e) {
            DatabaseHelper::rollback();
            $errors[] = 'Review submission failed: ' . $e->getMessage();
        }
    }
}

$page_title = 'Facility Condition Review';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/form.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/table.css">
</head>
<body>
    <?php include __DIR__ . '/../templates/common/header.php'; ?>
    <?php include __DIR__ . '/../templates/common/menu.php'; ?>
    <div id="error-division" class="error-banner" style="display: <?php echo !empty($errors) ? 'block' : 'none'; ?>;">
        <?php foreach ($errors as $error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
    <?php if ($success): ?>
        <div class="success-banner">
            <p>Facility review saved successfully.</p>
        </div>
    <?php endif; ?>
    <div class="body-content">
        <h1>Facility Condition Review</h1>
        <form method="POST" action="">
            <div class="form-group">
                <label for="inspection_date">Inspection Date <span class="required">*</span></label>
                <input type="date" id="inspection_date" name="inspection_date" value="<?php echo htmlspecialchars($inspection_date); ?>" required>
            </div>
            <div class="form-group">
                <label for="facility_name">Facility Name <span class="required">*</span></label>
                <input type="text" id="facility_name" name="facility_name" value="<?php echo htmlspecialchars($facility_name); ?>" required>
            </div>
            <div class="form-group">
                <label for="inspector_name">Inspector Name <span class="required">*</span></label>
                <input type="text" id="inspector_name" name="inspector_name" value="<?php echo htmlspecialchars($inspector_name); ?>" required>
            </div>
            <div class="form-group">
                <label for="notes">Inspection Notes</label>
                <textarea id="notes" name="notes" rows="4"><?php echo htmlspecialchars($notes); ?></textarea>
            </div>

            <?php if (empty($categories)): ?>
                <div class="empty-state">
                    No review categories are configured yet. Add categories in the admin area.
                </div>
            <?php else: ?>
                <?php foreach ($categories as $category): ?>
                    <h2><?php echo htmlspecialchars($category['name']); ?></h2>
                    <?php if (!empty($category['description'])): ?>
                        <p><?php echo htmlspecialchars($category['description']); ?></p>
                    <?php endif; ?>
                    <?php if (empty($itemsByCategory[(int)$category['id']])): ?>
                        <p class="empty-state">No active inspection items in this category.</p>
                    <?php else: ?>
                        <div class="items-table-wrapper">
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Condition</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($itemsByCategory[(int)$category['id']] as $item): ?>
                                        <?php $ratingName = 'rating_' . (int)$item['id']; ?>
                                        <?php $noteName = 'notes_' . (int)$item['id']; ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                                <?php if (!empty($item['description'])): ?>
                                                    <br><small><?php echo htmlspecialchars($item['description']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <select name="<?php echo $ratingName; ?>" required>
                                                    <option value="">Select</option>
                                                    <option value="good" <?php echo ($item['default_severity'] === 'good') ? 'selected' : ''; ?>>Good</option>
                                                    <option value="fair" <?php echo ($item['default_severity'] === 'fair') ? 'selected' : ''; ?>>Fair</option>
                                                    <option value="poor" <?php echo ($item['default_severity'] === 'poor') ? 'selected' : ''; ?>>Poor</option>
                                                    <option value="critical" <?php echo ($item['default_severity'] === 'critical') ? 'selected' : ''; ?>>Critical</option>
                                                </select>
                                            </td>
                                            <td>
                                                <textarea name="<?php echo $noteName; ?>" rows="2"></textarea>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Facility Review</button>
            </div>
        </form>
    </div>
    <?php include __DIR__ . '/../templates/common/footer.php'; ?>
</body>
</html>
