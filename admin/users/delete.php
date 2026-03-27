<?php
/**
 * Delete User Page
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . BASE_PATH . '/admin/users/');
    exit;
}

$user = DatabaseHelper::queryOne(
    "SELECT u.id, u.name, u.client_id, c.name AS client_name
       FROM users u
       JOIN clients c ON c.id = u.client_id
      WHERE u.id = ?",
    [$id]
);
if (!$user) {
    header('Location: ' . BASE_PATH . '/admin/users/');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = FormHelper::getPost('confirm', '');
    if ($confirm !== 'yes') {
        $errors[] = 'Please confirm deletion by checking the box.';
    }

    if (empty($errors)) {
        $rows = DatabaseHelper::execute("DELETE FROM users WHERE id = ?", [$id]);
        if ($rows > 0) {
            header('Location: ' . BASE_PATH . '/admin/users/?client_id=' . (int)$user['client_id']);
            exit;
        } else {
            $errors[] = 'Failed to delete user.';
        }
    }
}

$page_title = 'Delete User';
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
    <h1>Delete User</h1>
    <div class="body-content">
        <?php if (!empty($errors)): ?>
            <div class="error-banner">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="item-detail-card">
            <p>You are about to delete user: <strong><?php echo htmlspecialchars($user['name']); ?></strong>
               (Client: <?php echo htmlspecialchars($user['client_name']); ?>)</p>
        </div>
        <form method="POST" action="">
            <div class="form-group form-check">
                <input type="checkbox" id="confirm" name="confirm" value="yes">
                <label for="confirm">Yes, I confirm I want to delete this user.</label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-danger">Delete User</button>
                <a href="<?php echo BASE_PATH; ?>/admin/users/?client_id=<?php echo (int)$user['client_id']; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
