<?php
/**
 * Add User Page
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';

$errors  = [];
$success = false;

// Pre-select client if coming from clients page
$preselect_client = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;

$clients = DatabaseHelper::queryAll("SELECT id, name FROM clients ORDER BY name", []);
if (empty($clients)) {
    header('Location: ' . BASE_PATH . '/admin/clients/add.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = FormHelper::getPost('name', '');
    $email      = strtolower(trim(FormHelper::getPost('email', '')));
    $client_id  = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $is_admin   = isset($_POST['is_admin'])   ? 1 : 0;

    if (!FormHelper::isRequired($name)) {
        $errors[] = 'User name is required.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email address is not valid.';
    }
    if ($client_id <= 0) {
        $errors[] = 'A client must be selected.';
    } else {
        $client_exists = DatabaseHelper::queryOne("SELECT id FROM clients WHERE id = ?", [$client_id]);
        if (!$client_exists) {
            $errors[] = 'Selected client does not exist.';
        }
    }

    if (empty($errors)) {
        if ($is_default) {
            DatabaseHelper::execute("UPDATE users SET is_default = 0 WHERE client_id = ?", [$client_id]);
        }
        $rows = DatabaseHelper::execute(
            "INSERT INTO users (client_id, name, email, is_default, is_admin) VALUES (?, ?, ?, ?, ?)",
            [$client_id, $name, $email ?: null, $is_default, $is_admin]
        );
        if ($rows > 0) {
            header('Location: ' . BASE_PATH . '/admin/users/?client_id=' . $client_id);
            exit;
        } else {
            $errors[] = 'Failed to create user. The name may already exist for this client.';
        }
    }
} else {
    $name       = '';
    $email      = '';
    $client_id  = $preselect_client;
    $is_default = 0;
    $is_admin   = 0;
}

$page_title = 'Add User';
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
    <h1>Add User</h1>
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
                <label for="client_id">Client <span class="required">*</span></label>
                <select id="client_id" name="client_id" required>
                    <option value="">— Select Client —</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>"
                            <?php echo ($client_id === (int)$c['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="name">User Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" required
                       value="<?php echo htmlspecialchars($name); ?>" maxlength="128">
            </div>
            <div class="form-group">
                <label for="email">Microsoft Email Address</label>
                <input type="email" id="email" name="email"
                       value="<?php echo htmlspecialchars($email); ?>" maxlength="255"
                       placeholder="user@organisation.com">
                <small>Enter the user's Microsoft / Entra ID email so they can log in.</small>
            </div>
            <div class="form-group form-check">
                <input type="checkbox" id="is_default" name="is_default" value="1"
                    <?php echo $is_default ? 'checked' : ''; ?>>
                <label for="is_default">Set as default user for this client</label>
            </div>
            <div class="form-group form-check">
                <input type="checkbox" id="is_admin" name="is_admin" value="1"
                    <?php echo $is_admin ? 'checked' : ''; ?>>
                <label for="is_admin">Admin user (can create and manage root items)</label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create User</button>
                <a href="<?php echo BASE_PATH; ?>/admin/users/" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
