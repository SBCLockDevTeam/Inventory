<?php
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/client_helper.php';

if (!ClientHelper::isActiveUserAdmin()) {
    header('Location: ' . BASE_PATH . '/home.php');
    exit;
}

$errors = [];
$success = false;
$category_id = '';
$code = '';
$name = '';
$description = '';
$default_severity = 'good';
$sort_order = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)($_POST['category_id'] ?? 0);
    $code = trim((string)($_POST['code'] ?? ''));
    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $default_severity = in_array($_POST['default_severity'] ?? '', ['good','fair','poor','critical'], true) ? $_POST['default_severity'] : 'good';
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    if ($category_id <= 0) {
        $errors[] = 'Please select a category.';
    }
    if ($code === '') {
        $errors[] = 'Item code is required.';
    }
    if ($name === '') {
        $errors[] = 'Item name is required.';
    }

    if (empty($errors)) {
        try {
            DatabaseHelper::execute(
                "INSERT INTO facility_review_items (category_id, code, name, description, default_severity, sort_order) VALUES (?, ?, ?, ?, ?, ?)",
                [$category_id, $code, $name, $description, $default_severity, $sort_order]
            );
            $success = true;
            $code = '';
            $name = '';
            $description = '';
            $default_severity = 'good';
            $sort_order = 0;
        } catch (Exception $e) {
            $errors[] = 'Review item save failed: ' . $e->getMessage();
        }
    }
}

$categories = DatabaseHelper::queryAll(
    "SELECT id, name FROM facility_review_categories ORDER BY sort_order, name",
    []
);
$items = DatabaseHelper::queryAll(
    "SELECT fri.id, frc.name AS category_name, fri.code, fri.name, fri.description, fri.default_severity, fri.sort_order, fri.is_active
     FROM facility_review_items fri
     JOIN facility_review_categories frc ON frc.id = fri.category_id
     ORDER BY frc.sort_order, frc.name, fri.sort_order, fri.name",
    []
);

$page_title = 'Review Items';
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
    <?php include __DIR__ . '/../../templates/common/header.php'; ?>
    <?php include __DIR__ . '/../../templates/common/menu.php'; ?>
    <div id="error-division" class="error-banner" style="display: <?php echo !empty($errors) ? 'block' : 'none'; ?>;">
        <?php foreach ($errors as $error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
    <?php if ($success): ?>
        <div class="success-banner"><p>Inspection item added successfully.</p></div>
    <?php endif; ?>
    <div class="body-content">
        <h1>Review Inspection Items</h1>
        <form method="POST" action="">
            <div class="form-group">
                <label for="category_id">Category <span class="required">*</span></label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int)$category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="code">Item Code <span class="required">*</span></label>
                <input type="text" id="code" name="code" value="<?php echo htmlspecialchars($code); ?>" required>
            </div>
            <div class="form-group">
                <label for="name">Item Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            <div class="form-group">
                <label for="default_severity">Default Severity</label>
                <select id="default_severity" name="default_severity">
                    <option value="good" <?php echo $default_severity === 'good' ? 'selected' : ''; ?>>Good</option>
                    <option value="fair" <?php echo $default_severity === 'fair' ? 'selected' : ''; ?>>Fair</option>
                    <option value="poor" <?php echo $default_severity === 'poor' ? 'selected' : ''; ?>>Poor</option>
                    <option value="critical" <?php echo $default_severity === 'critical' ? 'selected' : ''; ?>>Critical</option>
                </select>
            </div>
            <div class="form-group">
                <label for="sort_order">Sort Order</label>
                <input type="number" id="sort_order" name="sort_order" value="<?php echo (int)$sort_order; ?>" min="0">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Review Item</button>
            </div>
        </form>

        <div class="items-table-wrapper" style="margin-top:2rem;">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Default</th>
                        <th>Active</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="5" class="no-results">No review items found</td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['code']); ?></td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['default_severity']); ?></td>
                                <td><?php echo (int)$item['is_active'] ? 'Yes' : 'No'; ?></td>
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
