<?php
/**
 * Edit Client Page
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . BASE_PATH . '/admin/clients/');
    exit;
}

$client = DatabaseHelper::queryOne("SELECT id, name, description, is_default FROM clients WHERE id = ?", [$id]);
if (!$client) {
    header('Location: ' . BASE_PATH . '/admin/clients/');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = FormHelper::getPost('name', '');
    $description = FormHelper::getPost('description', '');
    $is_default  = isset($_POST['is_default']) ? 1 : 0;

    if (!FormHelper::isRequired($name)) {
        $errors[] = 'Client name is required.';
    }

    if (empty($errors)) {
        if ($is_default) {
            DatabaseHelper::execute("UPDATE clients SET is_default = 0 WHERE id != ?", [$id]);
        }
        $rows = DatabaseHelper::execute(
            "UPDATE clients SET name = ?, description = ?, is_default = ? WHERE id = ?",
            [$name, $description, $is_default, $id]
        );
        if ($rows !== false) {
            header('Location: ' . BASE_PATH . '/admin/clients/');
            exit;
        } else {
            $errors[] = 'Failed to update client. The name may already be in use.';
        }
    }
} else {
    $name        = $client['name'];
    $description = $client['description'] ?? '';
    $is_default  = (int)$client['is_default'];
}

$page_title = 'Edit Client';
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
    <h1>Edit Client</h1>
    <div class="body-content">
        <?php if (!empty($errors)): ?>
            <div class="error-banner">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Client Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" required
                       value="<?php echo htmlspecialchars($name); ?>" maxlength="128">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            <div class="form-group form-check">
                <input type="checkbox" id="is_default" name="is_default" value="1"
                    <?php echo $is_default ? 'checked' : ''; ?>>
                <label for="is_default">Set as default client</label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="<?php echo BASE_PATH; ?>/admin/clients/" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
