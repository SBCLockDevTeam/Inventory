<?php
/**
 * Public Item Lookup
 * Accepts ?Q={public_code} and displays item details without requiring login.
 * QR codes printed on labels link to this page.
 */
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/lib/database.php';
require_once __DIR__ . '/lib/form_helpers.php';
require_once __DIR__ . '/lib/location_helper.php';
require_once __DIR__ . '/lib/field_helper.php';

$item_code = isset($_GET['Q']) ? trim($_GET['Q']) : '';

// No valid code – send authenticated users to the home page
if (!FormHelper::isValidHex10($item_code)) {
    // Send a 404 header and exit silently
    http_response_code(404);
    exit;
}

$item = DatabaseHelper::queryOne(
    "SELECT public_code, name, description, is_container,
            location_item_id, primary_image, created_at, updated_at, last_seen_at
       FROM items
      WHERE public_code = ?",
    [$item_code]
);

if (!$item) {
    $page_title = 'Item Not Found';
} else {
    $page_title    = htmlspecialchars($item['name']);
    $fields        = FieldHelper::getFields($item_code);
    $scalar_values = FieldHelper::getScalarValues($item_code);
    $all_photos    = FieldHelper::getAllPhotos($item_code);
    $all_docs      = FieldHelper::getAllDocuments($item_code);
    $all_sigs      = FieldHelper::getAllSignatures($item_code);

    // Record this QR scan as the last time the item was seen.
    // Re-read the timestamp the DB just wrote so the displayed value
    // is consistent with what is stored (avoids PHP/DB timezone drift).
    DatabaseHelper::execute(
        "UPDATE items SET last_seen_at = NOW() WHERE public_code = ?",
        [$item_code]
    );
    $refreshed = DatabaseHelper::queryOne(
        "SELECT last_seen_at FROM items WHERE public_code = ?",
        [$item_code]
    );
    $item['last_seen_at'] = $refreshed['last_seen_at'] ?? date('Y-m-d H:i:s');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/location.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/photo_upload.css">
</head>
<body>
    <div class="body-content">
        <?php if (!$item): ?>
        <h1>Item Not Found</h1>
        <p>No item matched the provided code. Please check your QR label and try again.</p>
        <?php else: ?>

        <!-- Unified item card: core details + dynamic field values as one block -->
        <div class="item-detail-card">

            <!-- Core item details -->
            <div class="item-card-section">
                <div class="item-detail-grid item-detail-grid--single">
                    <div class="item-detail-field item-detail-field--full">
                        <span class="field-label">Name</span>
                        <span class="field-value"><?php echo htmlspecialchars($item['name']); ?></span>
                    </div>
                    <div class="item-detail-field item-detail-field--full">
                        <span class="field-label">Description</span>
                        <span class="field-value"><?php echo nl2br(htmlspecialchars($item['description'] ?? '')); ?></span>
                    </div>
                    <div class="item-detail-field item-detail-field--full">
                        <span class="field-label">Created</span>
                        <span class="field-value"><?php echo htmlspecialchars($item['created_at'] ?? '—'); ?></span>
                    </div>
                    <div class="item-detail-field item-detail-field--full">
                        <span class="field-label">Last Seen</span>
                        <span class="field-value"><?php echo htmlspecialchars($item['last_seen_at'] ?? '—'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Dynamic field values (read-only) -->
            <?php if (!empty($fields)): ?>
            <div class="item-card-section field-values-section">
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

        <div class="actions-bottom">
            <a href="<?php echo BASE_PATH; ?>/items/edit.php?id=<?php echo htmlspecialchars($item_code); ?>"
               class="btn btn-primary">Edit</a>
        </div>

        <?php endif; ?>
    </div>
</body>
</html>
