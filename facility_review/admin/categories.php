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
$name = '';
$description = '';
$sort_order = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    if ($name === '') {
        $errors[] = 'Category name is required.';
    }

    if (empty($errors)) {
        try {
            DatabaseHelper::execute(
                "INSERT INTO facility_review_categories (name, description, sort_order) VALUES (?, ?, ?)",
                [$name, $description, $sort_order]
            );
            $success = true;
            $name = '';
            $description = '';
            $sort_order = 0;
        } catch (Exception $e) {
            $errors[] = 'Category save failed: ' . $e->getMessage();
        }
    }
}

$categories = DatabaseHelper::queryAll(
    "SELECT id, name, description, sort_order FROM facility_review_categories ORDER BY sort_order, name",
    []
);

$page_title = 'Review Categories';
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
        <div class="success-banner"><p>Category added successfully.</p></div>
    <?php endif; ?>
    <div class="body-content">
        <h1>Review Categories</h1>
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Category Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            <div class="form-group">
                <label for="sort_order">Sort Order</label>
                <input type="number" id="sort_order" name="sort_order" value="<?php echo (int)$sort_order; ?>" min="0">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Category</button>
            </div>
        </form>

        <div class="items-table-wrapper" style="margin-top:2rem;">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Sort</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr><td colspan="3" class="no-results">No categories found</td></tr>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo htmlspecialchars($category['description'] ?? ''); ?></td>
                                <td><?php echo (int)$category['sort_order']; ?></td>
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
