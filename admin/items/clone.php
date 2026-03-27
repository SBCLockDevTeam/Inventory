<?php
/**
 * Clone Item
 *
 * Presents two options:
 *  1. Clone structure only — new item gets the same field definitions but blank values
 *  2. Clone structure + data — new item gets the same field definitions AND copied scalar values
 *
 * Photos, documents, and signatures are NOT cloned (they are physical files and
 * would need separate handling; users can upload new ones).
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';
require_once __DIR__ . '/../../lib/location_helper.php';
require_once __DIR__ . '/../../lib/client_helper.php';
require_once __DIR__ . '/../../lib/field_helper.php';

$source_id = FormHelper::getGet('id');
if (!FormHelper::isValidHex10($source_id)) {
    header('Location: ' . BASE_PATH . '/admin/items/');
    exit;
}

$source = DatabaseHelper::queryOne(
    "SELECT public_code, name, description, is_container, location_item_id, client_id
       FROM items WHERE public_code = ?",
    [$source_id]
);
if (!$source) {
    header('Location: ' . BASE_PATH . '/admin/items/');
    exit;
}

$active_client        = ClientHelper::getActiveClient();
$active_user_is_admin = ClientHelper::isActiveUserAdmin();
$active_user          = ClientHelper::getActiveUser();

$errors  = [];
$success = false;
$new_code = '';

// Suggest a unique ID when the page first loads (GET request)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $new_code = DatabaseHelper::generateUniqueCode();
}

// Pre-populate form with sensible defaults
$new_name        = $source['name'] . ' (Copy)';
$new_description = $source['description'] ?? '';
$new_is_container = $source['is_container'];
$clone_data      = 0;
$new_parent      = $source['location_item_id'] === $source['public_code'] ? 'root' : $source['location_item_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_code         = FormHelper::getPost('public_code');
    $new_name         = FormHelper::getPost('name');
    $new_description  = FormHelper::getPost('description');
    $new_is_container = isset($_POST['is_container']) ? 1 : 0;
    $clone_data       = isset($_POST['clone_data']) ? 1 : 0;

    $parent_raw  = FormHelper::getPost('location_item_id');
    $make_root   = ($parent_raw === '' || $parent_raw === 'root');
    $new_parent  = $parent_raw;

    if ($make_root && !$active_user_is_admin) {
        $errors[] = 'Only admin users may create root items.';
        $make_root = false;
    }

    // Validate new public code
    if (!FormHelper::isRequired($new_code)) {
        $errors[] = 'Item ID is required';
    } elseif (!FormHelper::isValidHex10($new_code)) {
        $errors[] = 'Item ID must be exactly 10 hexadecimal characters (0-9, a-f)';
    } else {
        $exists = DatabaseHelper::queryOne("SELECT public_code FROM items WHERE public_code = ?", [$new_code]);
        if ($exists) {
            $errors[] = 'Item ID already exists. Please choose a different ID.';
        }
    }

    if (!FormHelper::isRequired($new_name)) {
        $errors[] = 'Item Name is required';
    }

    if (!FormHelper::isRequired($new_description)) {
        $errors[] = 'Item Description is required';
    }

    if ($make_root) {
        $client_id_check = $active_client ? (int)$active_client['id'] : null;
        if ($client_id_check !== null) {
            $existing_root = DatabaseHelper::queryOne(
                "SELECT public_code FROM items WHERE client_id = ? AND location_item_id = public_code LIMIT 1",
                [$client_id_check]
            );
            if ($existing_root) {
                $errors[] = 'This client already has a root item. Each client may only have one root item.';
            }
        }
    } else {
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
        $resolved_location = $make_root ? $new_code : $parent_raw;
        $client_id_val     = $active_client ? (int)$active_client['id'] : null;

        DatabaseHelper::beginTransaction();
        try {
            // Insert the new item
            DatabaseHelper::execute(
                "INSERT INTO items (public_code, name, description, is_container, location_item_id, client_id)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$new_code, $new_name, $new_description, $new_is_container, $resolved_location, $client_id_val]
            );

            // Copy field definitions from source item
            $source_fields  = FieldHelper::getFields($source_id);
            // Pre-fetch scalar values once (used when clone_data = 1)
            $source_scalars = $clone_data ? FieldHelper::getScalarValues($source_id) : [];
            foreach ($source_fields as $sf) {
                DatabaseHelper::execute(
                    "INSERT INTO item_fields
                         (item_public_code, field_key, label, field_type, required, sort_order,
                          allow_multiple, instructions, require_printed_name)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $new_code,
                        $sf['field_key'],
                        $sf['label'],
                        $sf['field_type'],
                        $sf['required'],
                        $sf['sort_order'],
                        $sf['allow_multiple'],
                        $sf['instructions'],
                        $sf['require_printed_name'],
                    ]
                );

                // If cloning data, copy scalar values for text/textarea/number/date/checkbox fields
                if ($clone_data && in_array($sf['field_type'], ['text','textarea','number','date','checkbox'])) {
                    $new_field_id = (int)DatabaseHelper::getLastInsertId();
                    if ($new_field_id > 0) {
                        $src_val = $source_scalars[$sf['id']] ?? null;
                        if ($src_val) {
                            DatabaseHelper::execute(
                                "INSERT INTO item_field_values
                                     (item_public_code, field_id, value_text, value_number, value_date, value_bool)
                                 VALUES (?, ?, ?, ?, ?, ?)",
                                [
                                    $new_code,
                                    $new_field_id,
                                    $src_val['value_text'],
                                    $src_val['value_number'],
                                    $src_val['value_date'],
                                    $src_val['value_bool'],
                                ]
                            );
                        }
                    }
                }
            }

            DatabaseHelper::commit();
            $success = true;

            // Log the clone event
            $user_label = $active_user ? $active_user['name'] : null;
            FieldHelper::logGeneral(
                'item_cloned',
                $new_code,
                null,
                $source_id,
                $new_code,
                'Clone ' . ($clone_data ? 'with data' : 'structure only') . ' from ' . $source_id,
                $user_label
            );

        } catch (Exception $e) {
            DatabaseHelper::rollback();
            $errors[] = 'Clone failed: ' . $e->getMessage();
        }
    }
}

$source_fields        = FieldHelper::getFields($source_id);
$available_containers = LocationHelper::getAllContainers([], $active_client ? (int)$active_client['id'] : null);
$page_title           = 'Clone Item – ' . htmlspecialchars($source['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/form.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/location.css">
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
            <p>Item cloned successfully! New item ID: <strong><?php echo htmlspecialchars($new_code); ?></strong></p>
            <a href="<?php echo BASE_PATH; ?>/admin/items/view.php?id=<?php echo htmlspecialchars($new_code); ?>" class="btn btn-primary">View New Item</a>
        </div>
    <?php endif; ?>

    <h1>Clone Item</h1>

    <div class="body-content">
        <div class="item-detail-card" style="margin-bottom:1.5rem;">
            <h2>Source Item</h2>
            <p><strong><?php echo htmlspecialchars($source['name']); ?></strong>
               <code>(<?php echo htmlspecialchars($source['public_code']); ?>)</code></p>
            <p><?php echo htmlspecialchars($source['description'] ?? ''); ?></p>
            <?php if (!empty($source_fields)): ?>
                <p><small><?php echo count($source_fields); ?> custom field<?php echo count($source_fields) !== 1 ? 's' : ''; ?> will be copied.</small></p>
            <?php else: ?>
                <p><small>This item has no custom fields.</small></p>
            <?php endif; ?>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="public_code">New Item ID (10 hex digits) <span class="required">*</span></label>
                <input type="text" id="public_code" name="public_code" maxlength="10"
                       pattern="[0-9a-fA-F]{10}" placeholder="e.g., 1a2b3c4d5e"
                       value="<?php echo htmlspecialchars($new_code); ?>" required>
                <small>Exactly 10 hexadecimal characters</small>
            </div>

            <div class="form-group">
                <label for="name">Item Name <span class="required">*</span></label>
                <input type="text" id="name" name="name"
                       value="<?php echo htmlspecialchars($new_name); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Item Description <span class="required">*</span></label>
                <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($new_description); ?></textarea>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="is_container" name="is_container"
                           <?php echo $new_is_container ? 'checked' : ''; ?>>
                    <span>This item is a container</span>
                </label>
            </div>

            <div class="form-group">
                <label>Clone Mode <span class="required">*</span></label>
                <label class="checkbox-label" style="margin-top:0.5rem;">
                    <input type="checkbox" name="clone_data" value="1"
                           <?php echo $clone_data ? 'checked' : ''; ?>>
                    <span>Copy field values as well as field definitions</span>
                </label>
                <small>When unchecked, only the field structure is copied — values are left blank.</small>
            </div>

            <div class="form-group">
                <label for="location_item_id">Parent Location <?php echo !$active_user_is_admin ? '<span class="required">*</span>' : ''; ?></label>
                <select id="location_item_id" name="location_item_id">
                    <?php if ($active_user_is_admin): ?>
                    <option value="root" <?php echo ($new_parent === 'root') ? 'selected' : ''; ?>>— No parent (Root item) —</option>
                    <?php endif; ?>
                    <?php foreach ($available_containers as $container): ?>
                        <option value="<?php echo htmlspecialchars($container['public_code']); ?>"
                            <?php echo ($new_parent === $container['public_code']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($container['name']); ?>
                            (<?php echo htmlspecialchars($container['public_code']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Clone</button>
                <a href="<?php echo BASE_PATH; ?>/admin/items/view.php?id=<?php echo $source['public_code']; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
