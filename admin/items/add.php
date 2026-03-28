<?php
/**
 * Create New Item
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';
require_once __DIR__ . '/../../lib/location_helper.php';
require_once __DIR__ . '/../../lib/client_helper.php';

$errors       = [];
$success      = false;
$public_code  = '';
$name         = '';
$description  = '';
$is_container = 0;
$location_item_id = 'root';

$active_user_is_admin = ClientHelper::isActiveUserAdmin();

// Suggest a unique ID when the page first loads (GET request)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $public_code = DatabaseHelper::generateUniqueCode();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $public_code  = FormHelper::getPost('public_code');
    $name         = FormHelper::getPost('name');
    $description  = FormHelper::getPost('description');
    $is_container = isset($_POST['is_container']) ? 1 : 0;

    $parent_raw       = FormHelper::getPost('location_item_id');
    $make_root        = ($parent_raw === '' || $parent_raw === 'root');
    $location_item_id = $parent_raw;

    // Only admins may create root items
    if ($make_root && !$active_user_is_admin) {
        $errors[] = 'Only admin users may create root items. Please select a parent container.';
        $make_root = false;
    }

    if (!FormHelper::isRequired($public_code)) {
        $errors[] = 'Item ID is required';
    } elseif (!FormHelper::isValidHex10($public_code)) {
        $errors[] = 'Item ID must be exactly 10 hexadecimal characters (0-9, a-f)';
    } else {
        $existing = DatabaseHelper::queryOne("SELECT public_code FROM items WHERE public_code = ?", [$public_code]);
        if ($existing) {
            $errors[] = 'Item ID already exists. Please choose a different ID.';
        }
    }

    if (!FormHelper::isRequired($name)) {
        $errors[] = 'Item Name is required';
    }

    if (!FormHelper::isRequired($description)) {
        $errors[] = 'Item Description is required';
    }

    if ($make_root) {
        // Single-tenant: only one root item is allowed site-wide
        $existing_root = DatabaseHelper::queryOne(
            "SELECT public_code FROM items WHERE location_item_id = public_code LIMIT 1",
            []
        );
        if ($existing_root) {
            $errors[] = 'A root item already exists (' . htmlspecialchars($existing_root['public_code']) . '). Only one root item is allowed.';
        }
    } else {
        // Verify parent exists and is a container
        $parent = DatabaseHelper::queryOne(
            "SELECT public_code, is_container FROM items WHERE public_code = ?",
            [$parent_raw]
        );
        if (!$parent) {
            $errors[] = 'Selected parent location does not exist';
        } elseif (!$parent['is_container']) {
            $errors[] = 'Selected parent location is not a container';
        }
    }

    if (empty($errors)) {
        // Root item is its own parent; otherwise use the selected container
        $resolved_location = $make_root ? $public_code : $parent_raw;

        $affected = DatabaseHelper::execute(
            "INSERT INTO items (public_code, name, description, is_container, location_item_id) VALUES (?, ?, ?, ?, ?)",
            [$public_code, $name, $description, $is_container, $resolved_location]
        );

        if ($affected > 0) {
            $success          = true;
            // Suggest a fresh unique ID ready for the next item
            $public_code      = DatabaseHelper::generateUniqueCode();
            $name             = '';
            $description      = '';
            $is_container     = 0;
            $location_item_id = 'root';
        } else {
            $errors[] = 'Database insert failed: ' . DatabaseHelper::getLastError();
        }
    }
}

$available_containers = LocationHelper::getAllContainers([]);
$root_item = DatabaseHelper::queryOne(
    "SELECT public_code, name FROM items WHERE location_item_id = public_code ORDER BY public_code LIMIT 1",
    []
);
// Default new items to the root container
if ($location_item_id === 'root' || $location_item_id === '') {
    $location_item_id = $root_item ? $root_item['public_code'] : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Item</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/form.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/location.css">
    <script src="<?php echo JS_PATH; ?>script.js" defer></script>
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
        <div class="success-banner">
            <p class="success">Item created successfully!</p>
        </div>
    <?php endif; ?>
    <h1>Create New Item</h1>
    <div class="body-content">
        <form method="POST" action="" class="form-create-item">
            <div class="form-group">
                <label for="public_code">Item ID (10 hex digits) <span class="required">*</span></label>
                <input type="text" id="public_code" name="public_code" maxlength="10"
                       pattern="[0-9a-fA-F]{10}" placeholder="e.g., 1a2b3c4d5e"
                       value="<?php echo htmlspecialchars($public_code); ?>" required>
                <small>Exactly 10 hexadecimal characters (0-9, a-f, A-F)</small>
            </div>
            <div class="form-group">
                <label for="name">Item Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" placeholder="Enter item name"
                       value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Item Description <span class="required">*</span></label>
                <textarea id="description" name="description" placeholder="Enter detailed description"
                          rows="5" required><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="is_container" name="is_container"
                           <?php echo ($is_container == 1) ? 'checked' : ''; ?>>
                    <span>This item is a container (can hold other items)</span>
                </label>
            </div>
            <div class="form-group">
                <label for="location_item_id">Parent Location <span class="required">*</span></label>
                <select id="location_item_id" name="location_item_id" required>
                    <?php if (empty($available_containers)): ?>
                    <option value="" disabled selected>— No containers available —</option>
                    <?php endif; ?>
                    <?php foreach ($available_containers as $container): ?>
                        <option value="<?php echo htmlspecialchars($container['public_code']); ?>"
                            <?php echo ($location_item_id === $container['public_code']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($container['name']); ?>
                            (<?php echo htmlspecialchars($container['public_code']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="location-selector-hint">
                    Choose the container this item will live in.
                </small>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Item</button>
                <a href="<?php echo BASE_PATH; ?>/admin/items/" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
