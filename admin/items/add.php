<?php
/**
 * Create New Item
 */
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';

$errors = [];
$success = false;
$public_code = '';
$name = '';
$description = '';
$is_container = 0;
$brand_id = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $public_code = FormHelper::getPost('public_code');
    $name = FormHelper::getPost('name');
    $description = FormHelper::getPost('description');
    $is_container = isset($_POST['is_container']) ? 1 : 0;
    $brand_id = (int)FormHelper::getPost('brand_id', 1);

    if (!FormHelper::isRequired($public_code)) {
        $errors[] = 'Item ID is required';
    } elseif (!FormHelper::isValidHex10($public_code)) {
        $errors[] = 'Item ID must be exactly 10 hexadecimal characters (0-9, a-f)';
    }

    if (!FormHelper::isRequired($name)) {
        $errors[] = 'Item Name is required';
    }

    if (!FormHelper::isRequired($description)) {
        $errors[] = 'Item Description is required';
    }

    if (!FormHelper::isRequired($brand_id) || $brand_id <= 0) {
        $errors[] = 'Brand selection is required';
    }

    if (empty($errors)) {
        $location_item_id = $public_code;
        $sql = "INSERT INTO items (public_code, brand_id, name, description, is_container, location_item_id) VALUES (?, ?, ?, ?, ?, ?)";
        $affected = DatabaseHelper::execute($sql, [$public_code, $brand_id, $name, $description, $is_container, $location_item_id], 'isssii');

        if ($affected > 0) {
            $success = true;
            $public_code = '';
            $name = '';
            $description = '';
            $is_container = 0;
            $brand_id = 1;
        } else {
            $errors[] = 'Database insert failed: ' . DatabaseHelper::getLastError();
        }
    }
}

$brands = DatabaseHelper::queryAll("SELECT id, name FROM brands ORDER BY name", []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Item</title>
    <link rel="stylesheet" href="/qr/css/style.css">
    <link rel="stylesheet" href="/qr/css/components/form.css">
</head>
<body>
    <?php include __DIR__ . '/../../templates/common/header.php'; ?>
    <?php include __DIR__ . '/../../templates/common/menu.php'; ?>
    <div id="error-division" class="error-banner" style="display: <?php echo !empty($errors) ? 'block' : 'none'; ?>;">
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        <?php endif; ?>
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
                <input type="text" id="public_code" name="public_code" maxlength="10" pattern="[0-9a-fA-F]{10}" placeholder="e.g., 1a2b3c4d5e" value="<?php echo htmlspecialchars($public_code); ?>" required>
                <small>Exactly 10 hexadecimal characters (0-9, a-f, A-F)</small>
            </div>
            <div class="form-group">
                <label for="brand_id">Brand <span class="required">*</span></label>
                <select id="brand_id" name="brand_id" required>
                    <option value="">-- Select Brand --</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?php echo $brand['id']; ?>" <?php echo ($brand_id == $brand['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($brand['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="name">Item Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" placeholder="Enter item name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Item Description <span class="required">*</span></label>
                <textarea id="description" name="description" placeholder="Enter detailed description" rows="5" required><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="is_container" name="is_container" <?php echo ($is_container == 1) ? 'checked' : ''; ?>>
                    <span>This item is a container (can hold other items)</span>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Item</button>
                <a href="/qr/admin/items/" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
</body>
</html>
