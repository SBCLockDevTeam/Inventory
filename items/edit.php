<?php
/**
 * Edit Item – unified page to update item details and field values.
 * Handles core properties (name, description, container flag, location) and
 * dynamic scalar field values in one form submission.
 * Photo, document, and signature fields continue to be handled via AJAX.
 * Prevents circular references: an item cannot be placed inside one of its own descendants.
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/form_helpers.php';
require_once __DIR__ . '/../lib/location_helper.php';
require_once __DIR__ . '/../lib/client_helper.php';
require_once __DIR__ . '/../lib/field_helper.php';

$item_id = FormHelper::getGet('id');
if (!FormHelper::isValidHex10($item_id)) {
    header('Location: ' . BASE_PATH . '/items/');
    exit;
}

$item = DatabaseHelper::queryOne(
    "SELECT public_code, name, description, is_container, location_item_id
       FROM items
      WHERE public_code = ?",
    [$item_id]
);

if (!$item) {
    header('Location: ' . BASE_PATH . '/items/');
    exit;
}

$errors  = [];

$active_user          = ClientHelper::getActiveUser();
$active_user_is_admin = ClientHelper::isActiveUserAdmin();

// Seed form fields from existing item
$name             = $item['name'];
$description      = $item['description'];
$is_container     = $item['is_container'];
$location_item_id = $item['location_item_id'];
$is_root          = ($item['location_item_id'] === $item['public_code']);

// Load dynamic fields and current values for rendering and saving
$fields        = FieldHelper::getFields($item_id);
$scalar_values = FieldHelper::getScalarValues($item_id);
$all_photos    = FieldHelper::getAllPhotos($item_id);
$all_docs      = FieldHelper::getAllDocuments($item_id);
$all_sigs      = FieldHelper::getAllSignatures($item_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ----------------------------------------------------------------
    // Part 1: Save core item details
    // ----------------------------------------------------------------
    $name         = FormHelper::getPost('name');
    $description  = FormHelper::getPost('description');
    $is_container = isset($_POST['is_container']) ? 1 : 0;

    $new_parent_raw = FormHelper::getPost('location_item_id');
    // Empty string or 'root' means make this a root item (its own parent)
    $make_root  = ($new_parent_raw === '' || $new_parent_raw === 'root');
    $new_parent = $make_root ? $item_id : $new_parent_raw;

    // Only admins may promote an item to a root item
    if ($make_root && !$active_user_is_admin) {
        $errors[] = 'Only admin users may make an item a root item. Please select a parent container.';
        $make_root  = false;
        $new_parent = $new_parent_raw;
    }

    // Validation
    if (!FormHelper::isRequired($name)) {
        $errors[] = 'Item Name is required';
    }

    if (!FormHelper::isRequired($description)) {
        $errors[] = 'Item Description is required';
    }

    if ($make_root) {
        // If the item is not already a root item, enforce single root site-wide
        if (!$is_root) {
            $existing_root = DatabaseHelper::queryOne(
                "SELECT public_code FROM items WHERE location_item_id = public_code AND public_code != ? LIMIT 1",
                [$item_id]
            );
            if ($existing_root) {
                $errors[] = 'A root item already exists (' . htmlspecialchars($existing_root['public_code']) . '). Only one root item is allowed.';
            }
        }
    } else {
        // Verify parent exists and is a container
        $parent = DatabaseHelper::queryOne(
            "SELECT public_code, is_container FROM items WHERE public_code = ?",
            [$new_parent]
        );
        if (!$parent) {
            $errors[] = 'Selected parent location does not exist';
        } elseif (!$parent['is_container']) {
            $errors[] = 'Selected parent location is not a container';
        } else {
            // Prevent moving item into one of its own descendants (circular reference)
            $descendants = LocationHelper::getDescendantCodes($item_id);
            if (in_array($new_parent, $descendants)) {
                $errors[] = 'Cannot move item into one of its own descendants (circular reference)';
            }
        }
    }

    if (empty($errors)) {
        $affected = DatabaseHelper::execute(
            "UPDATE items SET name = ?, description = ?, is_container = ?, location_item_id = ? WHERE public_code = ?",
            [$name, $description, $is_container, $new_parent, $item_id]
        );

        if ($affected >= 0) {
            $location_item_id = $new_parent;
            $is_root          = ($new_parent === $item_id);
        } else {
            $errors[] = 'Database update failed: ' . DatabaseHelper::getLastError();
        }
    }

    // ----------------------------------------------------------------
    // Part 2: Save scalar field values (photo/doc/sig are handled by AJAX)
    // ----------------------------------------------------------------
    foreach ($fields as $field) {
        $ftype = $field['field_type'];
        // File-based fields are saved asynchronously; skip them here
        if (in_array($ftype, ['photo', 'document', 'signature'])) {
            continue;
        }

        $field_id = (int)$field['id'];
        $post_key = 'field_' . $field_id;

        if ($ftype === 'checkbox') {
            // Checkbox: presence in POST = checked
            $raw_value = isset($_POST[$post_key]) ? 1 : 0;
        } else {
            $raw_value = FormHelper::getPost($post_key);
        }

        // Required check
        if ($field['required'] && $ftype !== 'checkbox' && $raw_value === '') {
            $errors[] = htmlspecialchars($field['label']) . ' is required';
            continue;
        }

        $ok = FieldHelper::saveScalarValue($item_id, $field_id, $ftype, $raw_value);
        if (!$ok) {
            $errors[] = 'Failed to save field: ' . htmlspecialchars($field['label']);
        }
    }

    if (empty($errors)) {
        $user_label = $active_user ? $active_user['name'] : null;
        FieldHelper::logGeneral('item_updated', $item_id, null, null, null, null, $user_label);
        header('Location: ' . BASE_PATH . '/items/view.php?id=' . urlencode($item_id));
        exit;
    }
}

// Exclude the item itself and all its descendants from the parent dropdown
$descendants_to_exclude   = LocationHelper::getDescendantCodes($item_id);
$descendants_to_exclude[] = $item_id;
$available_containers     = LocationHelper::getAllContainers($descendants_to_exclude);

$breadcrumb = LocationHelper::getLocationBreadcrumb($item_id);
$page_title = 'Edit Item – ' . htmlspecialchars($item['name']);
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
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/photo_upload.css">
    <script src="<?php echo JS_PATH; ?>script.js" defer></script>
    <script src="<?php echo JS_PATH; ?>lib/photo_capture.js" defer></script>
    <script src="<?php echo JS_PATH; ?>lib/signature_capture.js" defer></script>
    <script src="<?php echo JS_PATH; ?>pages/fill_item.js" defer></script>
    <script src="<?php echo JS_PATH; ?>pages/edit_item.js" defer></script>
</head>
<body data-base-path="<?php echo BASE_PATH; ?>">
    <?php include __DIR__ . '/../templates/common/header.php'; ?>
    <?php include __DIR__ . '/../templates/common/menu.php'; ?>
    <div id="error-division" class="error-banner" style="display: <?php echo !empty($errors) ? 'block' : 'none'; ?>;">
        <?php foreach ($errors as $error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>

    <div class="body-content">

        <!-- Current location breadcrumb -->
        <?php if (!empty($breadcrumb)): ?>
        <nav class="location-breadcrumb" aria-label="Item location">
            <ul class="breadcrumb-list">
                <?php foreach ($breadcrumb as $crumb): ?>
                    <li>
                        <a class="<?php echo $crumb['is_container'] ? 'breadcrumb-container-icon' : 'breadcrumb-item-icon'; ?>"
                           href="<?php echo BASE_PATH; ?>/items/view.php?id=<?php echo $crumb['public_code']; ?>">
                            <?php echo htmlspecialchars($crumb['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <form method="POST" action="" class="form-create-item">

            <!-- ── Section 1: Core item properties ─────────────────────── -->
            <div class="form-group">
                <label for="name">Item Name <span class="required">*</span></label>
                <input type="text" id="name" name="name"
                       value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Item Description <span class="required">*</span></label>
                <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="is_container" name="is_container"
                           <?php echo ($is_container == 1) ? 'checked' : ''; ?>>
                    <span>This item is a container (can hold other items)</span>
                </label>
            </div>

            <!-- Location / Parent selector -->
            <div class="form-group">
                <label for="location_item_id">Parent Location <span class="required">*</span></label>
                <?php if ($is_root): ?>
                <input type="hidden" name="location_item_id" value="root">
                <p class="location-selector-hint">This is the root item and has no parent.</p>
                <?php else: ?>
                <select id="location_item_id" name="location_item_id" required>
                    <?php foreach ($available_containers as $container): ?>
                        <option value="<?php echo htmlspecialchars($container['public_code']); ?>"
                            <?php echo ($location_item_id === $container['public_code']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($container['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="location-selector-hint">
                    Choose the container this item lives in.
                    The item itself and its descendants are excluded from this list.
                </small>
                <?php endif; ?>
            </div>

            <!-- ── Section 2: Dynamic field values ─────────────────────── -->
            <?php $field_count = count($fields); ?>
            <?php if (!empty($fields)): ?>
            <hr style="margin: 1.5rem 0;">
            <h2 style="margin-bottom:1rem;">Field Values</h2>
            <div id="fields-list-container">
            <?php foreach ($fields as $field):
                $fid       = (int)$field['id'];
                $ftype     = $field['field_type'];
                $label     = htmlspecialchars($field['label']);
                $required  = $field['required'];
                $multi     = $field['allow_multiple'];
                $instrText = htmlspecialchars($field['instructions'] ?? '');
                $sv        = $scalar_values[$fid] ?? null;
            ?>
            <div class="field-block"
                 data-field-id="<?php echo $fid; ?>"
                 data-field-type="<?php echo htmlspecialchars($ftype); ?>"
                 data-item-code="<?php echo htmlspecialchars($item['public_code']); ?>"
                 data-allow-multiple="<?php echo $multi ? '1' : '0'; ?>"
                 data-require-printed-name="<?php echo $field['require_printed_name'] ? '1' : '0'; ?>"
                 data-instructions="<?php echo $instrText; ?>">

                <?php if ($active_user_is_admin): ?>
                <div class="field-block-controls">
                    <button type="button" class="btn btn-small field-order-up"
                            data-field-id="<?php echo $fid; ?>"
                            data-item-code="<?php echo htmlspecialchars($item['public_code']); ?>"
                            title="Move Up">▲</button>
                    <button type="button" class="btn btn-small field-order-down"
                            data-field-id="<?php echo $fid; ?>"
                            data-item-code="<?php echo htmlspecialchars($item['public_code']); ?>"
                            title="Move Down">▼</button>
                    <button type="button" class="btn btn-small btn-danger field-delete-inline"
                            data-field-id="<?php echo $fid; ?>"
                            data-field-label="<?php echo $label; ?>"
                            data-item-code="<?php echo htmlspecialchars($item['public_code']); ?>"
                            title="Delete this field">✕ Delete Field</button>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>
                        <?php echo $label; ?>
                        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
                        <small class="field-type-badge"><?php echo htmlspecialchars(ucfirst($ftype)); ?></small>
                    </label>

                    <?php if ($instrText): ?>
                        <small class="field-instructions"><?php echo $instrText; ?></small>
                    <?php endif; ?>

                    <?php if ($ftype === 'text'): ?>
                        <input type="text" name="field_<?php echo $fid; ?>"
                               value="<?php echo htmlspecialchars($sv['value_text'] ?? ''); ?>"
                               <?php echo $required ? 'required' : ''; ?>>

                    <?php elseif ($ftype === 'textarea'): ?>
                        <textarea name="field_<?php echo $fid; ?>" rows="4"
                                  <?php echo $required ? 'required' : ''; ?>><?php echo htmlspecialchars($sv['value_text'] ?? ''); ?></textarea>

                    <?php elseif ($ftype === 'number'): ?>
                        <input type="number" step="any" name="field_<?php echo $fid; ?>"
                               value="<?php echo htmlspecialchars($sv['value_number'] ?? ''); ?>"
                               <?php echo $required ? 'required' : ''; ?>>

                    <?php elseif ($ftype === 'date'): ?>
                        <input type="date" name="field_<?php echo $fid; ?>"
                               value="<?php echo htmlspecialchars($sv['value_date'] ?? ''); ?>"
                               <?php echo $required ? 'required' : ''; ?>>

                    <?php elseif ($ftype === 'checkbox'): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="field_<?php echo $fid; ?>" value="1"
                                   <?php echo !empty($sv['value_bool']) ? 'checked' : ''; ?>>
                            <span><?php echo $label; ?></span>
                        </label>

                    <?php elseif ($ftype === 'photo'): ?>
                        <?php $photos = $all_photos[$fid] ?? []; ?>
                        <div class="photo-thumbnails" id="photo-container-<?php echo $fid; ?>">
                            <?php foreach ($photos as $photo): ?>
                                <div class="photo-thumb-wrap" data-image-id="<?php echo (int)$photo['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($photo['file_path']); ?>"
                                         alt="<?php echo htmlspecialchars($photo['caption'] ?? 'Photo'); ?>"
                                         onclick="window.open(this.src,'_blank')">
                                    <button type="button" class="photo-delete-btn"
                                            title="Remove photo"
                                            data-image-id="<?php echo (int)$photo['id']; ?>"
                                            data-item-code="<?php echo htmlspecialchars($item['public_code']); ?>">✕</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-secondary"
                                id="photo-trigger-<?php echo $fid; ?>">📷 Add Photo</button>

                    <?php elseif ($ftype === 'document'): ?>
                        <?php $docs = $all_docs[$fid] ?? []; ?>
                        <ul class="document-list" id="doc-container-<?php echo $fid; ?>">
                            <?php foreach ($docs as $doc): ?>
                                <li>
                                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>"
                                       target="_blank" rel="noopener">
                                        <?php echo htmlspecialchars($doc['original_filename']); ?>
                                    </a>
                                    <button type="button" class="doc-delete-btn"
                                            data-doc-id="<?php echo (int)$doc['id']; ?>">✕</button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <input type="file" id="doc-file-<?php echo $fid; ?>" style="display:none;"
                               <?php echo $multi ? 'multiple' : ''; ?>>
                        <button type="button" class="btn btn-secondary"
                                id="doc-trigger-<?php echo $fid; ?>">📎 Attach Document</button>

                    <?php elseif ($ftype === 'signature'): ?>
                        <?php $sigs = $all_sigs[$fid] ?? []; ?>
                        <div id="sig-container-<?php echo $fid; ?>">
                            <?php foreach ($sigs as $sig): ?>
                                <div class="signature-preview">
                                    <img src="<?php echo htmlspecialchars($sig['signature_image_path']); ?>"
                                         alt="Signature" style="max-width:100%;border:1px solid #bdc3c7;border-radius:4px;">
                                    <?php if (!empty($sig['printed_name'])): ?>
                                        <p style="font-size:0.85rem;">Signed by: <?php echo htmlspecialchars($sig['printed_name']); ?></p>
                                    <?php endif; ?>
                                    <p style="font-size:0.75rem;color:#7f8c8d;"><?php echo htmlspecialchars($sig['created_at']); ?></p>
                                    <button type="button" class="btn btn-small btn-danger sig-delete-btn"
                                            data-sig-id="<?php echo (int)$sig['id']; ?>"
                                            data-item-code="<?php echo htmlspecialchars($item['public_code']); ?>">Remove</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-secondary"
                                id="sig-trigger-<?php echo $fid; ?>">✍️ Sign</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div><!-- #fields-list-container -->
            <?php else: ?>
            <p style="margin-top:1rem;">No custom fields defined yet.</p>
            <?php endif; ?>

            <?php if ($active_user_is_admin): ?>
            <div class="field-add-section">
                <button type="button" class="btn btn-secondary" id="add-field-btn">+ Add Field</button>
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="<?php echo BASE_PATH; ?>/items/view.php?id=<?php echo $item['public_code']; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>

    </div>

    <?php if ($active_user_is_admin): ?>
    <!-- Add Field modal -->
    <div id="add-field-modal" class="add-field-modal-overlay">
        <div class="add-field-modal-dialog">
            <h3>Add New Field</h3>
            <form id="add-field-form">
                <input type="hidden" name="item_code" value="<?php echo htmlspecialchars($item['public_code']); ?>">
                <div class="form-group">
                    <label for="af-label">Field Label <span class="required">*</span></label>
                    <input type="text" id="af-label" name="label" required>
                </div>
                <div class="form-group">
                    <label for="af-field_type">Field Type <span class="required">*</span></label>
                    <select id="af-field_type" name="field_type" required>
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="photo">Photo</option>
                        <option value="document">Document</option>
                        <option value="signature">Signature</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="required" value="1">
                        <span>Required field</span>
                    </label>
                </div>
                <div class="form-group" id="af-multiple-group" style="display:none;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="allow_multiple" value="1">
                        <span>Allow multiple values</span>
                    </label>
                </div>
                <div class="form-group">
                    <label for="af-instructions">Instructions</label>
                    <textarea id="af-instructions" name="instructions" rows="3"></textarea>
                </div>
                <div class="form-group" id="af-printed-name-group" style="display:none;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="require_printed_name" value="1">
                        <span>Require printed name</span>
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Field</button>
                    <button type="button" class="btn btn-secondary add-field-modal-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php include __DIR__ . '/../templates/common/footer.php'; ?>
