<?php
/**
 * Edit Printer – admin only.
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';
require_once __DIR__ . '/../../lib/client_helper.php';

if (!ClientHelper::isActiveUserAdmin()) {
    header('Location: ' . BASE_PATH . '/home.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . BASE_PATH . '/admin/printers/');
    exit;
}

$printer = DatabaseHelper::queryOne("SELECT * FROM printers WHERE id = ?", [$id]);
if (!$printer) {
    header('Location: ' . BASE_PATH . '/admin/printers/');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = FormHelper::getPost('name', '');
    $host       = FormHelper::getPost('host', '');
    $port       = (int)FormHelper::getPost('port', '9100');
    $is_active  = isset($_POST['is_active'])  ? 1 : 0;
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $sort_order = (int)FormHelper::getPost('sort_order', '0');

    if (!FormHelper::isRequired($name)) {
        $errors[] = 'Printer name is required.';
    }
    if (!FormHelper::isRequired($host)) {
        $errors[] = 'Host is required.';
    }
    if ($port < 1 || $port > 65535) {
        $errors[] = 'Port must be between 1 and 65535.';
    }

    if (empty($errors)) {
        // Clear existing default when this one is set as default
        if ($is_default) {
            DatabaseHelper::execute("UPDATE printers SET is_default = 0 WHERE id != ?", [$id]);
        }
        $rows = DatabaseHelper::execute(
            "UPDATE printers SET name = ?, host = ?, port = ?, is_active = ?, is_default = ?, sort_order = ? WHERE id = ?",
            [$name, $host, $port, $is_active, $is_default, $sort_order, $id]
        );
        if ($rows !== false) {
            header('Location: ' . BASE_PATH . '/admin/printers/');
            exit;
        } else {
            $errors[] = 'Failed to update printer. The name may already be in use.';
        }
    }
} else {
    $name       = $printer['name'];
    $host       = $printer['host'];
    $port       = (int)$printer['port'];
    $is_active  = (int)$printer['is_active'];
    $is_default = (int)$printer['is_default'];
    $sort_order = (int)$printer['sort_order'];
}

$page_title = 'Edit Printer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/form.css">
    <script src="<?php echo JS_PATH; ?>script.js" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../templates/common/header.php'; ?>
    <?php include __DIR__ . '/../../templates/common/menu.php'; ?>
    <div id="error-division" class="error-banner" style="display: none;"></div>
    <h1>Edit Printer</h1>
    <div class="body-content">
        <?php if (!empty($errors)): ?>
            <div class="error-banner">
                <?php foreach ($errors as $err): ?>
                    <p><?php echo htmlspecialchars($err); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Printer Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" required maxlength="128"
                       value="<?php echo htmlspecialchars($name); ?>">
            </div>
            <div class="form-group">
                <label for="host">Hostname or URL <span class="required">*</span></label>
                <input type="text" id="host" name="host" required maxlength="255"
                       placeholder="e.g. pierround.com or 192.168.1.100"
                       value="<?php echo htmlspecialchars($host); ?>">
            </div>
            <div class="form-group">
                <label for="port">Port <span class="required">*</span></label>
                <input type="number" id="port" name="port" required min="1" max="65535"
                       value="<?php echo (int)$port; ?>">
            </div>
            <div class="form-group">
                <label for="sort_order">Sort Order</label>
                <input type="number" id="sort_order" name="sort_order" min="0"
                       value="<?php echo (int)$sort_order; ?>">
            </div>
            <div class="form-group form-check">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                       <?php echo $is_active ? 'checked' : ''; ?>>
                <label for="is_active">Active (available for printing)</label>
            </div>
            <div class="form-group form-check">
                <input type="checkbox" id="is_default" name="is_default" value="1"
                       <?php echo $is_default ? 'checked' : ''; ?>>
                <label for="is_default">Set as default printer</label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="<?php echo BASE_PATH; ?>/admin/printers/" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
</body>
</html>
