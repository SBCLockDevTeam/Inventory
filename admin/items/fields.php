<?php
/**
 * Manage Dynamic Fields for Item
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';

$item_id = FormHelper::getGet('id');
if (!FormHelper::isValidHex10($item_id)) {
    die('Invalid item ID');
}

$item = DatabaseHelper::queryOne("SELECT public_code, name FROM items WHERE public_code = ?", [$item_id]);
if (!$item) {
    die('Item not found');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $label = FormHelper::getPost('label');
    $field_type = FormHelper::getPost('field_type');
    $required = isset($_POST['required']) ? 1 : 0;
    $allow_multiple = isset($_POST['allow_multiple']) ? 1 : 0;
    $instructions = FormHelper::getPost('instructions');
    $require_printed_name = isset($_POST['require_printed_name']) ? 1 : 0;

    if (!FormHelper::isRequired($label)) {
        $errors[] = 'Label is required';
    }

    if (!in_array($field_type, ['text','textarea','number','date','checkbox','photo','document','signature'])) {
        $errors[] = 'Invalid field type';
    }

    if (empty($errors)) {
        $base_key   = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($label)));
        $field_key  = $base_key;
        $key_suffix = 2;
        while (DatabaseHelper::queryOne(
            "SELECT id FROM item_fields WHERE item_public_code = ? AND field_key = ?",
            [$item_id, $field_key]
        )) {
            $field_key = $base_key . '_' . $key_suffix++;
        }

        $max_sort   = DatabaseHelper::queryOne("SELECT MAX(sort_order) AS max_sort FROM item_fields WHERE item_public_code = ?", [$item_id]);
        $sort_order = ($max_sort && $max_sort['max_sort'] !== null) ? (int)$max_sort['max_sort'] + 1 : 1;

        $sql = "INSERT INTO item_fields (item_public_code, field_key, label, field_type, required, sort_order, allow_multiple, instructions, require_printed_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $affected = DatabaseHelper::execute($sql, [$item_id, $field_key, $label, $field_type, $required, $sort_order, $allow_multiple, $instructions, $require_printed_name]);

        if ($affected > 0) {
            $success = true;
            // Reset form
            $label = '';
            $field_type = 'text';
            $required = 0;
            $allow_multiple = 0;
            $instructions = '';
            $require_printed_name = 0;
        } else {
            $errors[] = 'Database insert failed: ' . DatabaseHelper::getLastError();
        }
    }
}

$fields = DatabaseHelper::queryAll("SELECT id, field_key, label, field_type, required, sort_order, allow_multiple, instructions, require_printed_name FROM item_fields WHERE item_public_code = ? ORDER BY sort_order", [$item_id]);
$page_title = 'Manage Fields - ' . htmlspecialchars($item['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/table.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/form.css">
    <script src="<?php echo JS_PATH; ?>script.js" defer></script>
    <script src="<?php echo JS_PATH; ?>pages/fields.js" defer></script>
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
            <p class="success">Field added successfully!</p>
        </div>
    <?php endif; ?>
    <h1>Manage Fields for Item: <?php echo htmlspecialchars($item['name']); ?></h1>
    <div class="body-content">
        <div class="current-fields">
            <h2>Current Fields</h2>
            <?php if (!empty($fields)): ?>
                <table class="fields-table">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Multiple</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fields as $field): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($field['label']); ?></td>
                                <td><?php echo htmlspecialchars($field['field_type']); ?></td>
                                <td><?php echo $field['required'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $field['allow_multiple'] ? 'Yes' : 'No'; ?></td>
                                <td class="actions">
                                    <a href="#" class="btn btn-small btn-danger field-delete-btn"
                                       data-field-id="<?php echo (int)$field['id']; ?>"
                                       data-field-label="<?php echo htmlspecialchars($field['label']); ?>"
                                       data-item-id="<?php echo htmlspecialchars($item['public_code']); ?>"
                                       data-delete-url="<?php echo BASE_PATH; ?>/admin/items/field_delete.php">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No custom fields defined yet.</p>
            <?php endif; ?>
        </div>

        <div class="add-field">
            <h2>Add New Field</h2>
            <form method="POST" action="" class="form-add-field">
                <div class="form-group">
                    <label for="label">Field Label <span class="required">*</span></label>
                    <input type="text" id="label" name="label" value="<?php echo htmlspecialchars($label ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="field_type">Field Type <span class="required">*</span></label>
                    <select id="field_type" name="field_type" required>
                        <option value="text" <?php echo ($field_type ?? 'text') == 'text' ? 'selected' : ''; ?>>Text</option>
                        <option value="textarea" <?php echo ($field_type ?? 'text') == 'textarea' ? 'selected' : ''; ?>>Textarea</option>
                        <option value="number" <?php echo ($field_type ?? 'text') == 'number' ? 'selected' : ''; ?>>Number</option>
                        <option value="date" <?php echo ($field_type ?? 'text') == 'date' ? 'selected' : ''; ?>>Date</option>
                        <option value="checkbox" <?php echo ($field_type ?? 'text') == 'checkbox' ? 'selected' : ''; ?>>Checkbox</option>
                        <option value="photo" <?php echo ($field_type ?? 'text') == 'photo' ? 'selected' : ''; ?>>Photo</option>
                        <option value="document" <?php echo ($field_type ?? 'text') == 'document' ? 'selected' : ''; ?>>Document</option>
                        <option value="signature" <?php echo ($field_type ?? 'text') == 'signature' ? 'selected' : ''; ?>>Signature</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="required" name="required" <?php echo ($required ?? 0) ? 'checked' : ''; ?>>
                        <span>Required field</span>
                    </label>
                </div>
                <div class="form-group" id="multiple-group" style="display: none;">
                    <label class="checkbox-label">
                        <input type="checkbox" id="allow_multiple" name="allow_multiple" <?php echo ($allow_multiple ?? 0) ? 'checked' : ''; ?>>
                        <span>Allow multiple values</span>
                    </label>
                </div>
                <div class="form-group">
                    <label for="instructions">Instructions</label>
                    <textarea id="instructions" name="instructions" rows="3"><?php echo htmlspecialchars($instructions ?? ''); ?></textarea>
                </div>
                <div class="form-group" id="printed-name-group" style="display: none;">
                    <label class="checkbox-label">
                        <input type="checkbox" id="require_printed_name" name="require_printed_name" <?php echo ($require_printed_name ?? 0) ? 'checked' : ''; ?>>
                        <span>Require printed name</span>
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Field</button>
                    <a href="<?php echo BASE_PATH; ?>/admin/items/" class="btn btn-secondary">Back to Items</a>
                </div>
            </form>
        </div>
    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>