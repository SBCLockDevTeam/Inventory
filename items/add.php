<?php
/**
 * Create New Item
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/form_helpers.php';
require_once __DIR__ . '/../lib/location_helper.php';
require_once __DIR__ . '/../lib/client_helper.php';
require_once __DIR__ . '/../lib/printer_helper.php';

$errors       = [];
$success      = false;
$public_code  = '';
$name         = '';
$description  = '';

// Load active printers for the optional "Print Label" checkbox
$printers            = PrinterHelper::getActivePrinters();
$selected_printer_id = PrinterHelper::getSelectedPrinterId($printers);

$print_items      = [];  // items queued for auto-print after success
$print_printer_id = 0;
$is_container = 0;
$location_item_id = 'root';
$parent_raw = '';
$add_count = 1;

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
    $add_count    = max(1, min(100, (int)(FormHelper::getPost('add_count') ?: 1)));

    $parent_raw       = FormHelper::getPost('location_item_id');
    $make_root        = ($parent_raw === '' || $parent_raw === 'root');
    $location_item_id = $parent_raw;

    // Only admins may create root items
    if ($make_root && !$active_user_is_admin) {
        $errors[] = 'Only admin users may create root items. Please select a parent container.';
        $make_root = false;
    }

    // Auto-fix the code if the pre-generated one is somehow invalid or already taken
    if (!FormHelper::isValidHex10($public_code)) {
        $public_code = DatabaseHelper::generateUniqueCode();
    } else {
        $existing = DatabaseHelper::queryOne("SELECT public_code FROM items WHERE public_code = ?", [$public_code]);
        if ($existing) {
            $public_code = DatabaseHelper::generateUniqueCode();
        }
    }

    if ($add_count === 1) {
        if (!FormHelper::isRequired($name)) {
            $errors[] = 'Item Name is required';
        }
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
            $errors[] = 'A root item already exists. Only one root item is allowed.';
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
        DatabaseHelper::beginTransaction();
        try {
            $added_items = [];
            for ($i = 0; $i < $add_count; $i++) {
                if ($add_count === 1) {
                    $item_name = $name;
                    $item_code = $public_code;
                } else {
                    $item_name = $name . ' ' . ($i + 1);
                    $item_code = DatabaseHelper::generateUniqueCode();
                }

                $resolved_location = $make_root ? $item_code : $parent_raw;

                DatabaseHelper::execute(
                    "INSERT INTO items (public_code, name, description, is_container, location_item_id) VALUES (?, ?, ?, ?, ?)",
                    [$item_code, $item_name, $description, $is_container, $resolved_location]
                );

                $added_items[] = ['code' => $item_code, 'name' => $item_name];
            }

            DatabaseHelper::commit();
            $success          = true;

            // Build the print queue before resetting form vars
            $print_printer_id = (int)FormHelper::getPost('printer_id');
            if (!empty($_POST['print_label']) && $print_printer_id > 0) {
                foreach ($added_items as $_ai) {
                    $print_items[] = [
                        'code'        => $_ai['code'],
                        'name'        => $_ai['name'],
                        'description' => $description,
                    ];
                }
            }

            // Suggest a fresh unique ID ready for the next item
            $public_code      = DatabaseHelper::generateUniqueCode();
            $name             = '';
            $description      = '';
            $is_container     = 0;
            $add_count        = 1;
            $location_item_id = $parent_raw;
        } catch (Exception $e) {
            DatabaseHelper::rollback();
            $errors[] = 'Add failed: ' . $e->getMessage();
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
    <?php include __DIR__ . '/../templates/common/header.php'; ?>
    <?php include __DIR__ . '/../templates/common/menu.php'; ?>
    <div id="error-division" class="error-banner" style="display: <?php echo !empty($errors) ? 'block' : 'none'; ?>;">
        <?php foreach ($errors as $error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
    <?php if ($success): ?>
        <div class="success-banner">
            <?php if (count($added_items) === 1): ?>
                <p>Item added successfully! New item ID: <strong><?php echo htmlspecialchars($added_items[0]['code']); ?></strong></p>
                <a href="<?php echo BASE_PATH; ?>/items/view.php?id=<?php echo htmlspecialchars($added_items[0]['code']); ?>" class="btn btn-primary">View New Item</a>
            <?php else: ?>
                <p><?php echo count($added_items); ?> items added successfully!</p>
                <ul>
                    <?php foreach ($added_items as $ai): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($ai['name']); ?></strong>
                            (<code><?php echo htmlspecialchars($ai['code']); ?></code>)
                            — <a href="<?php echo BASE_PATH; ?>/items/view.php?id=<?php echo htmlspecialchars($ai['code']); ?>">View</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="body-content">
        <form method="POST" action="" class="form-create-item">
            <input type="hidden" name="public_code" value="<?php echo htmlspecialchars($public_code); ?>">
            <div class="form-group" id="name-group">
                <label for="name">Item Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" placeholder="Enter item name"
                       value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            <div class="form-group" id="multi-add-note" style="display:none;">
                <p><small>Names and IDs will be auto-generated for each copy using sequential numbering.</small></p>
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
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="location-selector-hint">
                    Choose the container this item will live in.
                </small>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Item</button>
                <input type="number" id="add_count" name="add_count"
                       value="<?php echo (int)$add_count; ?>" min="1" max="100"
                       style="width:4.5rem; text-align:center;"
                       title="Number of copies to create">
                <label for="add_count" style="margin-left:0.25rem;">copies</label>
                <?php include __DIR__ . '/../templates/common/print_label_row.php'; ?>
                <a href="<?php echo BASE_PATH; ?>/items/" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <script src="<?php echo JS_PATH; ?>pages/print_label.js"></script>
    <script>
    (function () {
        var countInput   = document.getElementById('add_count');
        var nameGroup    = document.getElementById('name-group');
        var multiNote    = document.getElementById('multi-add-note');
        var nameInput    = document.getElementById('name');

        function toggleFields() {
            var multi = parseInt(countInput.value, 10) > 1;
            nameGroup.style.display  = multi ? 'none' : '';
            multiNote.style.display  = multi ? ''     : 'none';
            nameInput.required       = !multi;
        }

        countInput.addEventListener('input',  toggleFields);
        countInput.addEventListener('change', toggleFields);
        toggleFields();
    }());

    <?php if (!empty($print_items)): ?>
    // Auto-print labels for newly created items
    window.autoPrintLabels(
        <?php echo json_encode($print_items, JSON_HEX_TAG | JSON_HEX_AMP); ?>,
        <?php echo (int)$print_printer_id; ?>
    );
    <?php endif; ?>
    </script>
    <?php include __DIR__ . '/../templates/common/footer.php'; ?>
