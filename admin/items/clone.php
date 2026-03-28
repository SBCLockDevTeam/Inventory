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
 *
 * Supports cloning multiple copies at once via the clone_count field.
 * Names are auto-incremented: if the source name ends in a number that number is
 * incremented for each copy; otherwise "1" is appended and then incremented.
 * If the generated name already exists in the DB the number is bumped again.
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
    "SELECT public_code, name, description, is_container, location_item_id
       FROM items WHERE public_code = ?",
    [$source_id]
);
if (!$source) {
    header('Location: ' . BASE_PATH . '/admin/items/');
    exit;
}

$active_user_is_admin = ClientHelper::isActiveUserAdmin();
$active_user          = ClientHelper::getActiveUser();

/** Maximum number of copies that can be created in a single clone operation. */
define('CLONE_COUNT_MAX', 100);

/**
 * Split a name into its text prefix and trailing integer.
 * "Widget 5" → ["Widget ", 5]
 * "Widget"   → ["Widget ", 0]  (no trailing number; space appended so base."1" = "Widget 1")
 *
 * @param  string $name
 * @return array{0: string, 1: int}  [base_string, trailing_number]
 */
function cloneParseNameNumber(string $name): array {
    if (preg_match('/^(.*?)(\d+)$/', $name, $m)) {
        return [$m[1], (int)$m[2]];
    }
    return [$name . ' ', 0];
}

/**
 * Increment $num, then keep incrementing while $base.$num already exists in the DB.
 * Updates $num by reference and returns the unique candidate name.
 *
 * @param  string $base  The fixed prefix of the name.
 * @param  int    &$num  Current counter; mutated to the next available value.
 * @return string        The unique name ($base . $num).
 */
function cloneNextUniqueName(string $base, int &$num): string {
    $num++;
    $candidate = $base . $num;
    while (DatabaseHelper::queryOne("SELECT public_code FROM items WHERE name = ?", [$candidate])) {
        $num++;
        $candidate = $base . $num;
    }
    return $candidate;
}

$errors      = [];
$success     = false;
$new_code    = '';
$cloned_items = []; // [{code, name}] — populated on successful POST

// Suggest a unique ID when the page first loads (GET request)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $new_code = DatabaseHelper::generateUniqueCode();
}

// Compute the suggested next name from the source name.
// When the source has no trailing number it will be renamed to base."1" on POST,
// so the first available clone name begins at base."2" — start the counter at 1.
[$_name_base_default, $_name_num_default] = cloneParseNameNumber($source['name']);
$_name_num_tmp = $_name_num_default === 0 ? 1 : $_name_num_default;
$suggested_name = cloneNextUniqueName($_name_base_default, $_name_num_tmp);

// Pre-populate form with sensible defaults
$new_name         = $suggested_name;
$new_description  = $source['description'] ?? '';
$new_is_container = $source['is_container'];
$clone_data         = 0;
$clone_descendants  = 0;
$clone_count        = 1;
$new_parent       = $source['location_item_id'] === $source['public_code'] ? 'root' : $source['location_item_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_code         = FormHelper::getPost('public_code');
    $new_name         = FormHelper::getPost('name');
    $new_description  = FormHelper::getPost('description');
    $new_is_container  = isset($_POST['is_container']) ? 1 : 0;
    $clone_data        = isset($_POST['clone_data']) ? 1 : 0;
    $clone_descendants = isset($_POST['clone_descendants']) ? 1 : 0;
    $clone_count       = max(1, min(CLONE_COUNT_MAX, (int)(FormHelper::getPost('clone_count') ?: 1)));

    $parent_raw  = FormHelper::getPost('location_item_id');
    $make_root   = ($parent_raw === '' || $parent_raw === 'root');
    $new_parent  = $parent_raw;

    if ($make_root && !$active_user_is_admin) {
        $errors[] = 'Only admin users may create root items.';
        $make_root = false;
    }

    // Validate new public code only for single-clone (multi-clone auto-generates codes)
    if ($clone_count === 1) {
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
    }

    if (!FormHelper::isRequired($new_description)) {
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
        // Prepare name-increment state based on the SOURCE item name
        [$name_base, $name_num] = cloneParseNameNumber($source['name']);

        DatabaseHelper::beginTransaction();
        try {
            // Pre-fetch scalar values once (used when clone_data = 1)
            $source_fields  = FieldHelper::getFields($source_id);
            $source_scalars = $clone_data ? FieldHelper::getScalarValues($source_id) : [];

            $user_label = $active_user ? $active_user['name'] : null;

            // If the source name has no trailing number, rename it to base."1"
            // so clones can continue from base."2" onwards.
            if ($name_num === 0) {
                DatabaseHelper::execute(
                    "UPDATE items SET name = ? WHERE public_code = ?",
                    [$name_base . '1', $source_id]
                );
                $name_num = 1;
            }

            for ($i = 0; $i < $clone_count; $i++) {
                if ($clone_count === 1) {
                    // Single clone: use the form-supplied name and code
                    $item_name = $new_name;
                    $item_code = $new_code;
                } else {
                    // Multi-clone: auto-generate name and public code
                    $item_name = cloneNextUniqueName($name_base, $name_num);
                    $item_code = DatabaseHelper::generateUniqueCode();
                }

                $resolved_location = $make_root ? $item_code : $parent_raw;

                DatabaseHelper::execute(
                    "INSERT INTO items (public_code, name, description, is_container, location_item_id)
                     VALUES (?, ?, ?, ?, ?)",
                    [$item_code, $item_name, $new_description, $new_is_container, $resolved_location]
                );

                // Copy field definitions from source item
                foreach ($source_fields as $sf) {
                    DatabaseHelper::execute(
                        "INSERT INTO item_fields
                             (item_public_code, field_key, label, field_type, required, sort_order,
                              allow_multiple, instructions, require_printed_name)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $item_code,
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
                                        $item_code,
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

                $cloned_items[] = ['code' => $item_code, 'name' => $item_name];

                // Log each clone event
                FieldHelper::logGeneral(
                    'item_cloned',
                    $item_code,
                    null,
                    $source_id,
                    $item_code,
                    'Clone ' . ($clone_data ? 'with data' : 'structure only') . ' from ' . $source_id,
                    $user_label
                );

                // If requested, also clone every descendant of the source item,
                // preserving the original parent–child relationships beneath the new item.
                if ($clone_descendants) {
                    // BFS order guarantees each parent is mapped before its children
                    $desc_codes = LocationHelper::getDescendantCodes($source_id);
                    // Map: original public_code → new public_code
                    $code_map = [$source_id => $item_code];

                    foreach ($desc_codes as $desc_code) {
                        $desc = DatabaseHelper::queryOne(
                            "SELECT public_code, name, description, is_container, location_item_id
                               FROM items WHERE public_code = ?",
                            [$desc_code]
                        );
                        if (!$desc) {
                            // Descendant record missing mid-transaction; log and skip
                            FieldHelper::logGeneral(
                                'clone_warning',
                                $desc_code,
                                null,
                                null,
                                null,
                                'Descendant item not found during clone; skipped: ' . $desc_code,
                                $user_label
                            );
                            continue;
                        }

                        $new_desc_code   = DatabaseHelper::generateUniqueCode();
                        // Map the original parent to the already-cloned parent
                        $new_desc_parent = $code_map[$desc['location_item_id']] ?? $item_code;

                        DatabaseHelper::execute(
                            "INSERT INTO items (public_code, name, description, is_container, location_item_id)
                             VALUES (?, ?, ?, ?, ?)",
                            [$new_desc_code, $desc['name'], $desc['description'], $desc['is_container'], $new_desc_parent]
                        );

                        $code_map[$desc_code] = $new_desc_code;

                        // Clone field definitions (and optionally scalar values)
                        $desc_fields  = FieldHelper::getFields($desc_code);
                        $desc_scalars = $clone_data ? FieldHelper::getScalarValues($desc_code) : [];

                        foreach ($desc_fields as $df) {
                            DatabaseHelper::execute(
                                "INSERT INTO item_fields
                                     (item_public_code, field_key, label, field_type, required, sort_order,
                                      allow_multiple, instructions, require_printed_name)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                [
                                    $new_desc_code,
                                    $df['field_key'],
                                    $df['label'],
                                    $df['field_type'],
                                    $df['required'],
                                    $df['sort_order'],
                                    $df['allow_multiple'],
                                    $df['instructions'],
                                    $df['require_printed_name'],
                                ]
                            );

                            if ($clone_data && in_array($df['field_type'], ['text','textarea','number','date','checkbox'])) {
                                $new_df_id = (int)DatabaseHelper::getLastInsertId();
                                if ($new_df_id > 0) {
                                    $src_val = $desc_scalars[$df['id']] ?? null;
                                    if ($src_val) {
                                        DatabaseHelper::execute(
                                            "INSERT INTO item_field_values
                                                 (item_public_code, field_id, value_text, value_number, value_date, value_bool)
                                             VALUES (?, ?, ?, ?, ?, ?)",
                                            [
                                                $new_desc_code,
                                                $new_df_id,
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

                        FieldHelper::logGeneral(
                            'item_cloned',
                            $new_desc_code,
                            null,
                            $desc_code,
                            $new_desc_code,
                            'Clone descendant ' . ($clone_data ? 'with data' : 'structure only') . ' from ' . $desc_code,
                            $user_label
                        );
                    }
                }
            }

            DatabaseHelper::commit();
            $success  = true;
            $new_code = $cloned_items[0]['code'] ?? '';

        } catch (Exception $e) {
            DatabaseHelper::rollback();
            $errors[] = 'Clone failed: ' . $e->getMessage();
        }
    }
}

$source_fields        = FieldHelper::getFields($source_id);
$available_containers = LocationHelper::getAllContainers([]);
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
            <?php if (count($cloned_items) === 1): ?>
                <p>Item cloned successfully! New item ID: <strong><?php echo htmlspecialchars($cloned_items[0]['code']); ?></strong></p>
                <a href="<?php echo BASE_PATH; ?>/admin/items/view.php?id=<?php echo htmlspecialchars($cloned_items[0]['code']); ?>" class="btn btn-primary">View New Item</a>
            <?php else: ?>
                <p><?php echo count($cloned_items); ?> items cloned successfully!</p>
                <ul>
                    <?php foreach ($cloned_items as $ci): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($ci['name']); ?></strong>
                            (<code><?php echo htmlspecialchars($ci['code']); ?></code>)
                            — <a href="<?php echo BASE_PATH; ?>/admin/items/view.php?id=<?php echo htmlspecialchars($ci['code']); ?>">View</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
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
            <div class="form-group" id="public-code-group">
                <label for="public_code">New Item ID (10 hex digits) <span class="required">*</span></label>
                <input type="text" id="public_code" name="public_code" maxlength="10"
                       pattern="[0-9a-fA-F]{10}" placeholder="e.g., 1a2b3c4d5e"
                       value="<?php echo htmlspecialchars($new_code); ?>" required>
                <small>Exactly 10 hexadecimal characters</small>
            </div>

            <div class="form-group" id="name-group">
                <label for="name">Item Name <span class="required">*</span></label>
                <input type="text" id="name" name="name"
                       value="<?php echo htmlspecialchars($new_name); ?>" required>
            </div>

            <div class="form-group" id="multi-clone-note" style="display:none;">
                <p><small>Names and IDs will be auto-generated for each copy using sequential numbering.</small></p>
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
                <label class="checkbox-label">
                    <input type="checkbox" name="clone_descendants" value="1"
                           <?php echo $clone_descendants ? 'checked' : ''; ?>>
                    <span>Also clone source item's descendants (entire path)</span>
                </label>
                <small>When checked, all items nested beneath the source will also be cloned and placed under the new item.</small>
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
                <input type="number" id="clone_count" name="clone_count"
                       value="<?php echo (int)$clone_count; ?>" min="1" max="<?php echo CLONE_COUNT_MAX; ?>"
                       style="width:4.5rem; text-align:center;"
                       title="Number of copies to create">
                <label for="clone_count" style="margin-left:0.25rem;">copies</label>
                <a href="<?php echo BASE_PATH; ?>/admin/items/view.php?id=<?php echo $source['public_code']; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script>
    (function () {
        var countInput   = document.getElementById('clone_count');
        var codeGroup    = document.getElementById('public-code-group');
        var nameGroup    = document.getElementById('name-group');
        var multiNote    = document.getElementById('multi-clone-note');
        var codeInput    = document.getElementById('public_code');
        var nameInput    = document.getElementById('name');

        function toggleFields() {
            var multi = parseInt(countInput.value, 10) > 1;
            codeGroup.style.display  = multi ? 'none' : '';
            nameGroup.style.display  = multi ? 'none' : '';
            multiNote.style.display  = multi ? ''     : 'none';
            codeInput.required       = !multi;
            nameInput.required       = !multi;
        }

        countInput.addEventListener('input',  toggleFields);
        countInput.addEventListener('change', toggleFields);
        toggleFields();
    }());
    </script>

    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
