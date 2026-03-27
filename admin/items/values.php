<?php
/**
 * Item Field Values
 * Allows users to enter and update values for an item's dynamic fields.
 * Photo, document, and signature fields are handled via AJAX;
 * scalar fields (text, textarea, number, date, checkbox) are saved with the form.
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';
require_once __DIR__ . '/../../lib/client_helper.php';
require_once __DIR__ . '/../../lib/field_helper.php';

$item_id = FormHelper::getGet('id');
if (!FormHelper::isValidHex10($item_id)) {
    header('Location: ' . BASE_PATH . '/admin/items/');
    exit;
}

$item = DatabaseHelper::queryOne(
    "SELECT public_code, name, description, is_container FROM items WHERE public_code = ?",
    [$item_id]
);
if (!$item) {
    header('Location: ' . BASE_PATH . '/admin/items/');
    exit;
}

$active_user = ClientHelper::getActiveUser();
$errors      = [];
$success     = false;

$fields        = FieldHelper::getFields($item_id);
$scalar_values = FieldHelper::getScalarValues($item_id);
$all_photos    = FieldHelper::getAllPhotos($item_id);
$all_docs      = FieldHelper::getAllDocuments($item_id);
$all_sigs      = FieldHelper::getAllSignatures($item_id);

// ----------------------------------------------------------------
// Handle scalar field form save
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $field) {
        $ftype = $field['field_type'];
        // Photo / document / signature fields are handled by AJAX, skip here
        if (in_array($ftype, ['photo', 'document', 'signature'])) {
            continue;
        }

        $field_id = (int)$field['id'];
        $post_key = 'field_' . $field_id;

        if ($ftype === 'checkbox') {
            // Checkbox: presence in POST = checked; FormHelper::getPost would return '' for missing keys
            $raw_value = isset($_POST[$post_key]) ? 1 : 0;
        } else {
            // Use FormHelper to sanitize input (strips HTML, trims whitespace)
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
        $success = true;
        // Reload scalar values after save
        $scalar_values = FieldHelper::getScalarValues($item_id);

        // Log the field value update
        $user_label = $active_user ? $active_user['name'] : null;
        FieldHelper::logGeneral('field_values_updated', $item_id, null, null, null, null, $user_label);
    }
}

$page_title = 'Field Values – ' . htmlspecialchars($item['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/form.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/photo_upload.css">
    <script src="<?php echo JS_PATH; ?>script.js" defer></script>
    <script src="<?php echo JS_PATH; ?>lib/photo_capture.js" defer></script>
    <script src="<?php echo JS_PATH; ?>lib/signature_capture.js" defer></script>
    <script src="<?php echo JS_PATH; ?>pages/fill_item.js" defer></script>
</head>
<body data-base-path="<?php echo BASE_PATH; ?>">
    <?php include __DIR__ . '/../../templates/common/header.php'; ?>
    <?php include __DIR__ . '/../../templates/common/menu.php'; ?>

    <div id="error-division" class="error-banner" style="display: <?php echo !empty($errors) ? 'block' : 'none'; ?>;">
        <?php foreach ($errors as $error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endforeach; ?>
    </div>

    <?php if ($success): ?>
        <div class="success-banner"><p>Values saved successfully.</p></div>
    <?php endif; ?>

    <h1>Field Values: <?php echo htmlspecialchars($item['name']); ?></h1>

    <div class="body-content">

        <?php if (empty($fields)): ?>
            <p>This item has no custom fields defined yet.
               <a href="<?php echo BASE_PATH; ?>/admin/items/fields.php?id=<?php echo $item['public_code']; ?>" class="btn btn-secondary">Manage Fields</a>
            </p>
        <?php else: ?>
            <form method="POST" action="" enctype="multipart/form-data">
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

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Values</button>
                    <a href="<?php echo BASE_PATH; ?>/admin/items/view.php?id=<?php echo $item['public_code']; ?>"
                       class="btn btn-secondary">Back to Item</a>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
