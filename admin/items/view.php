<?php
/**
 * View Item - shows item details, location breadcrumb, and children.
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';
require_once __DIR__ . '/../../lib/location_helper.php';
require_once __DIR__ . '/../../lib/field_helper.php';

$item_id = FormHelper::getGet('id');
if (!FormHelper::isValidHex10($item_id)) {
    header('Location: ' . BASE_PATH . '/admin/items/');
    exit;
}

$item = DatabaseHelper::queryOne(
    "SELECT public_code, name, description, is_container,
            location_item_id, primary_image, created_at, updated_at
       FROM items
      WHERE public_code = ?",
    [$item_id]
);

if (!$item) {
    header('Location: ' . BASE_PATH . '/admin/items/');
    exit;
}

$breadcrumb     = LocationHelper::getLocationBreadcrumb($item_id);
$children       = $item['is_container'] ? LocationHelper::getDirectChildren($item_id) : [];
$page_title     = htmlspecialchars($item['name']);
$is_root        = ($item['location_item_id'] === $item['public_code']);
$fields         = FieldHelper::getFields($item_id);
$scalar_values  = FieldHelper::getScalarValues($item_id);
$all_photos     = FieldHelper::getAllPhotos($item_id);
$all_docs       = FieldHelper::getAllDocuments($item_id);
$all_sigs       = FieldHelper::getAllSignatures($item_id);

// Load active printers for the print-label bar; default printer used as fallback
$printers           = DatabaseHelper::queryAll(
    "SELECT id, name, is_default FROM printers WHERE is_active = 1 ORDER BY sort_order, name",
    []
);
$default_printer_id = 0;
foreach ($printers as $p) {
    if ($p['is_default']) {
        $default_printer_id = (int)$p['id'];
        break;
    }
}
// Fall back to the first active printer when no default is set
if ($default_printer_id === 0 && !empty($printers)) {
    $default_printer_id = (int)$printers[0]['id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> – View Item</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/table.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/location.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/photo_upload.css">
    <script src="<?php echo JS_PATH; ?>pages/print_label.js" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../templates/common/header.php'; ?>
    <?php include __DIR__ . '/../../templates/common/menu.php'; ?>
    <div id="error-division" class="error-banner" style="display: none;"></div>

    <h1><?php echo $page_title; ?></h1>

    <div class="body-content">

        <!-- Location breadcrumb -->
        <?php if (!empty($breadcrumb)): ?>
        <nav class="location-breadcrumb" aria-label="Item location">
            <ul class="breadcrumb-list">
                <?php foreach ($breadcrumb as $index => $crumb):
                    $isLast = ($index === count($breadcrumb) - 1);
                ?>
                    <li>
                        <?php if ($isLast): ?>
                            <span class="breadcrumb-current <?php echo $crumb['is_container'] ? 'breadcrumb-container-icon' : 'breadcrumb-item-icon'; ?>">
                                <?php echo htmlspecialchars($crumb['name']); ?>
                            </span>
                        <?php else: ?>
                            <a class="<?php echo $crumb['is_container'] ? 'breadcrumb-container-icon' : 'breadcrumb-item-icon'; ?>"
                               href="<?php echo BASE_PATH; ?>/admin/items/view.php?id=<?php echo $crumb['public_code']; ?>">
                                <?php echo htmlspecialchars($crumb['name']); ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <!-- Action buttons -->
        <div class="item-actions">
            <a href="<?php echo BASE_PATH; ?>/admin/items/edit.php?id=<?php echo $item['public_code']; ?>" class="btn btn-primary">Edit Item</a>
            <a href="<?php echo BASE_PATH; ?>/admin/items/clone.php?id=<?php echo $item['public_code']; ?>" class="btn btn-secondary">Clone Item</a>
            <a href="<?php echo BASE_PATH; ?>/admin/items/delete.php?id=<?php echo $item['public_code']; ?>" class="btn btn-danger">Delete Item</a>
            <a href="<?php echo BASE_PATH; ?>/admin/items/" class="btn btn-secondary">Back to Items</a>
        </div>

        <!-- Print Label bar -->
        <?php if (!empty($printers)): ?>
        <div class="print-bar">
            <label for="printer-select">Printer:</label>
            <select id="printer-select"
                    data-default="<?php echo $default_printer_id; ?>">
                <?php foreach ($printers as $p): ?>
                <option value="<?php echo (int)$p['id']; ?>"
                    <?php echo ((int)$p['id'] === $default_printer_id) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($p['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button id="btn-print-label"
                    class="btn btn-secondary"
                    type="button"
                    data-item-name="<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>"
                    data-description="<?php echo htmlspecialchars($item['description'] ?? '', ENT_QUOTES); ?>">
                🖨 Print Label
            </button>
            <span id="print-status" class="print-status"></span>
        </div>
        <?php endif; ?>

        <!-- Unified item card: core details + dynamic field values as one block -->
        <div class="item-detail-card">

            <!-- Item details section -->
            <div class="item-card-section">
                <h2>Item Details</h2>
                <div class="item-detail-grid">
                    <div class="item-detail-field">
                        <span class="field-label">Item ID</span>
                        <span class="field-value"><code><?php echo htmlspecialchars($item['public_code']); ?></code></span>
                    </div>
                    <div class="item-detail-field">
                        <span class="field-label">Name</span>
                        <span class="field-value"><?php echo htmlspecialchars($item['name']); ?></span>
                    </div>
                    <div class="item-detail-field">
                        <span class="field-label">Container</span>
                        <span class="field-value"><?php echo $item['is_container'] ? '✓ Yes' : 'No'; ?></span>
                    </div>
                    <div class="item-detail-field">
                        <span class="field-label">Location Type</span>
                        <span class="field-value"><?php echo $is_root ? 'Root item' : 'Child item'; ?></span>
                    </div>
                    <div class="item-detail-field">
                        <span class="field-label">Created</span>
                        <span class="field-value"><?php echo htmlspecialchars($item['created_at'] ?? '—'); ?></span>
                    </div>
                    <div class="item-detail-field item-detail-field--full">
                        <span class="field-label">Description</span>
                        <span class="field-value"><?php echo nl2br(htmlspecialchars($item['description'] ?? '')); ?></span>
                    </div>
                </div>
            </div>

            <!-- Dynamic field values (read-only summary), joined to the card above -->
            <?php if (!empty($fields)): ?>
            <hr class="item-card-divider">
            <div class="item-card-section field-values-section">
                <h2>Field Values</h2>
                <?php foreach ($fields as $field):
                    $fid   = (int)$field['id'];
                    $ftype = $field['field_type'];
                    $sv    = $scalar_values[$fid] ?? null;
                ?>
                <div class="field-value-row">
                    <span class="field-value-label"><?php echo htmlspecialchars($field['label']); ?></span>
                    <span class="field-value-content">
                        <?php if ($ftype === 'text' || $ftype === 'textarea'): ?>
                            <?php if (!empty($sv['value_text'])): ?>
                                <?php echo nl2br(htmlspecialchars($sv['value_text'])); ?>
                            <?php else: ?>
                                <span class="field-value-empty">—</span>
                            <?php endif; ?>

                        <?php elseif ($ftype === 'number'): ?>
                            <?php echo isset($sv['value_number']) && $sv['value_number'] !== null
                                       ? htmlspecialchars($sv['value_number'])
                                       : '<span class="field-value-empty">—</span>'; ?>

                        <?php elseif ($ftype === 'date'): ?>
                            <?php echo !empty($sv['value_date'])
                                       ? htmlspecialchars($sv['value_date'])
                                       : '<span class="field-value-empty">—</span>'; ?>

                        <?php elseif ($ftype === 'checkbox'): ?>
                            <?php echo isset($sv['value_bool']) && $sv['value_bool'] ? '✓ Yes' : 'No'; ?>

                        <?php elseif ($ftype === 'photo'): ?>
                            <?php $photos = $all_photos[$fid] ?? []; ?>
                            <?php if (!empty($photos)): ?>
                                <div class="photo-thumbnails">
                                    <?php foreach ($photos as $photo): ?>
                                        <div class="photo-thumb-wrap">
                                            <img src="<?php echo htmlspecialchars($photo['file_path']); ?>"
                                                 alt="Photo"
                                                 onclick="window.open(this.src,'_blank')"
                                                 style="cursor:pointer;">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="field-value-empty">No photos</span>
                            <?php endif; ?>

                        <?php elseif ($ftype === 'document'): ?>
                            <?php $docs = $all_docs[$fid] ?? []; ?>
                            <?php if (!empty($docs)): ?>
                                <ul class="document-list">
                                    <?php foreach ($docs as $doc): ?>
                                        <li>
                                            <a href="<?php echo htmlspecialchars($doc['file_path']); ?>"
                                               target="_blank" rel="noopener">
                                                <?php echo htmlspecialchars($doc['original_filename']); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="field-value-empty">No documents</span>
                            <?php endif; ?>

                        <?php elseif ($ftype === 'signature'): ?>
                            <?php $sigs = $all_sigs[$fid] ?? []; ?>
                            <?php if (!empty($sigs)): ?>
                                <?php foreach ($sigs as $sig): ?>
                                    <div style="margin-bottom:0.5rem;">
                                        <img src="<?php echo htmlspecialchars($sig['signature_image_path']); ?>"
                                             alt="Signature"
                                             style="max-width:200px;border:1px solid #bdc3c7;border-radius:4px;">
                                        <?php if (!empty($sig['printed_name'])): ?>
                                            <br><small>Signed by: <?php echo htmlspecialchars($sig['printed_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="field-value-empty">No signature</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>

        <!-- Children (only shown when this item is a container) -->
        <?php if ($item['is_container']): ?>
        <div class="children-section">
            <h2>Contents (<?php echo count($children); ?> item<?php echo count($children) !== 1 ? 's' : ''; ?>)</h2>
            <?php if (!empty($children)): ?>
            <table class="children-table">
                <thead>
                    <tr>
                        <th>Item ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($children as $child): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($child['public_code']); ?></code></td>
                        <td><?php echo htmlspecialchars($child['name']); ?></td>
                        <td>
                            <?php if ($child['is_container']): ?>
                                <span class="container-badge">Container</span>
                            <?php else: ?>
                                Item
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a href="<?php echo BASE_PATH; ?>/admin/items/view.php?id=<?php echo $child['public_code']; ?>" class="btn btn-small">View</a>
                            <a href="<?php echo BASE_PATH; ?>/admin/items/edit.php?id=<?php echo $child['public_code']; ?>" class="btn btn-small">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>This container is empty.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
