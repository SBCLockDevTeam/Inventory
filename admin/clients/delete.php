<?php
/**
 * Delete Client Page
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . BASE_PATH . '/admin/clients/');
    exit;
}

$client = DatabaseHelper::queryOne("SELECT id, name FROM clients WHERE id = ?", [$id]);
if (!$client) {
    header('Location: ' . BASE_PATH . '/admin/clients/');
    exit;
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = FormHelper::getPost('confirm', '');
    if ($confirm !== 'yes') {
        $errors[] = 'Please confirm deletion by checking the box.';
    }

    if (empty($errors)) {
        $rows = DatabaseHelper::execute("DELETE FROM clients WHERE id = ?", [$id]);
        if ($rows > 0) {
            header('Location: ' . BASE_PATH . '/admin/clients/');
            exit;
        } else {
            $errors[] = 'Failed to delete client.';
        }
    }
}

$user_count = DatabaseHelper::queryCount("SELECT COUNT(*) AS count FROM users WHERE client_id = ?", [$id]);
$item_count = DatabaseHelper::queryCount("SELECT COUNT(*) AS count FROM items WHERE client_id = ?", [$id]);

$page_title = 'Delete Client';
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
    <h1>Delete Client</h1>
    <div class="body-content">
        <?php if (!empty($errors)): ?>
            <div class="error-banner">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="item-detail-card">
            <p>You are about to delete client: <strong><?php echo htmlspecialchars($client['name']); ?></strong></p>
            <?php if ($user_count > 0): ?>
                <p class="warning">⚠ This client has <strong><?php echo $user_count; ?></strong> user(s) that will also be deleted.</p>
            <?php endif; ?>
            <?php if ($item_count > 0): ?>
                <p class="warning">⚠ This client has <strong><?php echo $item_count; ?></strong> item(s). Their client association will be removed.</p>
            <?php endif; ?>
        </div>
        <form method="POST" action="">
            <div class="form-group form-check">
                <input type="checkbox" id="confirm" name="confirm" value="yes">
                <label for="confirm">Yes, I confirm I want to delete this client.</label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-danger">Delete Client</button>
                <a href="<?php echo BASE_PATH; ?>/admin/clients/" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
