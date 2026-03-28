<?php
/**
 * Delete Printer – admin only.
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';
require_once __DIR__ . '/../../lib/client_helper.php';

if (!ClientHelper::isActiveUserAdmin()) {
    header('Location: ' . BASE_PATH . '/');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . BASE_PATH . '/admin/printers/');
    exit;
}

$printer = DatabaseHelper::queryOne("SELECT id, name FROM printers WHERE id = ?", [$id]);
if (!$printer) {
    header('Location: ' . BASE_PATH . '/admin/printers/');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = FormHelper::getPost('confirm', '');
    if ($confirm !== 'yes') {
        $errors[] = 'Please confirm deletion by checking the box.';
    }

    if (empty($errors)) {
        $rows = DatabaseHelper::execute("DELETE FROM printers WHERE id = ?", [$id]);
        if ($rows > 0) {
            header('Location: ' . BASE_PATH . '/admin/printers/');
            exit;
        } else {
            $errors[] = 'Failed to delete printer.';
        }
    }
}

$page_title = 'Delete Printer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <script src="<?php echo JS_PATH; ?>script.js" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../templates/common/header.php'; ?>
    <?php include __DIR__ . '/../../templates/common/menu.php'; ?>
    <div id="error-division" class="error-banner" style="display: none;"></div>
    <h1>Delete Printer</h1>
    <div class="body-content">
        <?php if (!empty($errors)): ?>
            <div class="error-banner">
                <?php foreach ($errors as $err): ?>
                    <p><?php echo htmlspecialchars($err); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="item-detail-card">
            <p>You are about to delete printer: <strong><?php echo htmlspecialchars($printer['name']); ?></strong></p>
            <p>This action cannot be undone.</p>
        </div>
        <form method="POST" action="">
            <div class="form-group form-check">
                <input type="checkbox" id="confirm" name="confirm" value="yes">
                <label for="confirm">Yes, I confirm I want to delete this printer.</label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-danger">Delete Printer</button>
                <a href="<?php echo BASE_PATH; ?>/admin/printers/" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
</body>
</html>
