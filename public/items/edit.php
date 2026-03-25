<?php
/**
 * Edit Item Page
 * Pre-populates all fields (core + custom) for an existing item.
 */

session_start();
require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/item_helpers.php';
require_once __DIR__ . '/../../lib/field_helpers.php';
require_once __DIR__ . '/../../lib/image_helpers.php';

$db = db();

$errors      = [];
$public_code = trim($_GET['id'] ?? '');

// ── Load item ────────────────────────────────────────────────────────────────
$item = $public_code ? getItemWithFields($db, $public_code) : null;

if (!$item) {
    http_response_code(404);
    add_error('error', 'Item not found.');
    header('Location: /public/inventory.php');
    exit;
}

$brands     = getBrands($db);
$containers = getContainers($db);

// Build existing field-value map:  field_id => value row
$field_values = getItemFieldValues($db, $public_code);

// ── POST handling ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name             = trim($_POST['name']             ?? '');
    $description      = trim($_POST['description']      ?? '');
    $brand_id         = (int)($_POST['brand_id']        ?? 0);
    $location_item_id = trim($_POST['location_item_id'] ?? '');
    $is_container     = !empty($_POST['is_container'])  ? 1 : 0;
    $remove_image     = !empty($_POST['remove_image']);

    // Validation
    if ($name === '') {
        $errors[] = 'Item name is required.';
    } elseif (strlen($name) > 255) {
        $errors[] = 'Item name must be 255 characters or fewer.';
    }

    if ($brand_id <= 0) {
        $errors[] = 'Please select a brand.';
    }

    if ($location_item_id === '') {
        $errors[] = 'Please select a parent location.';
    } elseif ($location_item_id !== $public_code) {
        // Validate parent exists and is a container
        $parent = queryOne($db,
            'SELECT public_code, is_container FROM items WHERE public_code = ?',
            [$location_item_id]
        );
        if (!$parent) {
            $errors[] = 'Selected parent location does not exist.';
        } elseif (!(int)$parent['is_container']) {
            $errors[] = 'Selected parent location is not a container.';
        } elseif (wouldCreateCircularReference($db, $public_code, $location_item_id)) {
            $errors[] = 'Cannot move item into one of its own descendants.';
        }
    }

    if (empty($errors)) {
        try {
            beginTransaction($db);

            // Image handling
            $new_image_path = $item['primary_image'];

            if ($remove_image && $item['primary_image']) {
                deleteImage($item['primary_image']);
                $new_image_path = null;
            }

            if (!empty($_FILES['primary_image']['name']) &&
                $_FILES['primary_image']['error'] === UPLOAD_ERR_OK) {
                // Replace existing image
                if ($item['primary_image']) {
                    deleteImage($item['primary_image']);
                }
                $new_image_path = handleImageUpload($_FILES['primary_image']);
            }

            // Update core item fields
            updateItem($db, $public_code, [
                'name'             => $name,
                'description'      => $description !== '' ? $description : null,
                'brand_id'         => $brand_id,
                'location_item_id' => $location_item_id,
                'is_container'     => $is_container,
                'primary_image'    => $new_image_path,
            ]);

            // Save custom field values
            foreach ($item['fields'] as $field_def) {
                $field_id  = (int)$field_def['id'];
                $post_key  = 'field_' . $field_id;
                $post_val  = $_POST[$post_key] ?? null;

                if ($field_def['field_type'] === 'checkbox') {
                    $post_val = isset($_POST[$post_key]) ? 1 : 0;
                }

                if ($post_val !== null) {
                    saveFieldValue($db, $public_code, $field_id, $post_val);
                }
            }

            commit($db);
            logActivity("Item updated: {$name} (Code: {$public_code})");

            // Reload updated item so re-render shows fresh data
            $item = getItemWithFields($db, $public_code);

            add_error('notice', 'Item updated successfully.');
            header('Location: view.php?id=' . urlencode($public_code) . '&updated=1');
            exit;

        } catch (Exception $e) {
            rollback($db);
            log_exception($e, 'item_update');
            $errors[] = 'Failed to update item: ' . $e->getMessage();
        }
    }
}

$page_title = 'Edit Item: ' . htmlspecialchars($item['name']);
?>
<?php include __DIR__ . '/../../templates/common/header.php'; ?>
<?php include __DIR__ . '/../../templates/common/menu.php'; ?>

<div class="container">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <!-- Breadcrumb -->
    <?php
    $breadcrumb = getItemBreadcrumb($db, $public_code);
    if (!empty($breadcrumb)):
    ?>
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <?php foreach ($breadcrumb as $i => $crumb): ?>
                <?php if ($i > 0): ?> &rsaquo; <?php endif; ?>
                <?php if ($i < count($breadcrumb) - 1): ?>
                    <a href="view.php?id=<?php echo urlencode($crumb['public_code']); ?>">
                        <?php echo htmlspecialchars($crumb['name']); ?>
                    </a>
                <?php else: ?>
                    <span><?php echo htmlspecialchars($crumb['name']); ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error-banner">
            <?php foreach ($errors as $e): ?>
                <p class="error"><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="item-form" novalidate>

        <!-- ── Core Fields ───────────────────────────────────────── -->
        <div class="form-section">
            <h2>Item Details</h2>

            <div class="form-group">
                <label for="name">Item Name <span class="required-star">*</span></label>
                <input type="text" id="name" name="name" class="form-control"
                       maxlength="255" required
                       value="<?php echo htmlspecialchars($_POST['name'] ?? $item['name']); ?>">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4"><?php
                    echo htmlspecialchars($_POST['description'] ?? ($item['description'] ?? ''));
                ?></textarea>
            </div>

            <div class="form-group">
                <label for="brand_id">Brand <span class="required-star">*</span></label>
                <select id="brand_id" name="brand_id" class="form-control" required>
                    <option value="">-- Select Brand --</option>
                    <?php
                    $selected_brand = (int)($_POST['brand_id'] ?? $item['brand_id']);
                    foreach ($brands as $brand):
                        $sel = $selected_brand === (int)$brand['id'] ? 'selected' : '';
                    ?>
                        <option value="<?php echo (int)$brand['id']; ?>" <?php echo $sel; ?>>
                            <?php echo htmlspecialchars($brand['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="location_item_id">Parent Location <span class="required-star">*</span></label>
                <select id="location_item_id" name="location_item_id" class="form-control" required>
                    <option value="">-- Select Parent Container --</option>
                    <?php
                    $selected_loc = $_POST['location_item_id'] ?? $item['location_item_id'];
                    // Root items are their own parent — keep that option visible
                    if (isRootItem($db, $public_code)):
                    ?>
                        <option value="<?php echo htmlspecialchars($public_code); ?>" selected>
                            (Root — this item is its own parent)
                        </option>
                    <?php else: ?>
                        <?php foreach ($containers as $c):
                            // Skip self
                            if ($c['public_code'] === $public_code) continue;
                            $sel = $selected_loc === $c['public_code'] ? 'selected' : '';
                        ?>
                            <option value="<?php echo htmlspecialchars($c['public_code']); ?>" <?php echo $sel; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group form-group-inline">
                <input type="checkbox" id="is_container" name="is_container" value="1"
                    <?php echo !empty($_POST['is_container'] ?? $item['is_container']) ? 'checked' : ''; ?>>
                <label for="is_container">This item is a container (can hold other items)</label>
            </div>
        </div>

        <!-- ── Custom Fields ──────────────────────────────────────── -->
        <?php if (!empty($item['fields'])): ?>
        <div class="form-section">
            <h2>Custom Fields</h2>
            <?php foreach ($item['fields'] as $field_def):
                $fid = (int)$field_def['id'];
                // Use POST value on re-render, otherwise stored value
                if (isset($_POST['field_' . $fid])) {
                    $cur_val = $_POST['field_' . $fid];
                } else {
                    $val_row = $field_values[$fid] ?? null;
                    if ($val_row) {
                        $cur_val = getFieldDisplayValue($val_row);
                        if ($field_def['field_type'] === 'checkbox') {
                            $cur_val = $val_row['value_bool'];
                        }
                    } else {
                        $cur_val = '';
                    }
                }
                renderFieldInput($field_def, $cur_val);
            endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ── Image Management ───────────────────────────────────── -->
        <div class="form-section">
            <h2>Primary Image</h2>

            <?php if (!empty($item['primary_image'])): ?>
                <div class="form-group">
                    <p><strong>Current image:</strong></p>
                    <img src="<?php echo htmlspecialchars($item['primary_image']); ?>"
                         alt="Current item image" class="item-image-preview"
                         style="max-width:200px; max-height:200px;">
                    <div class="form-group-inline" style="margin-top:8px;">
                        <input type="checkbox" id="remove_image" name="remove_image" value="1">
                        <label for="remove_image">Remove current image</label>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="primary_image"><?php echo empty($item['primary_image']) ? 'Upload Image' : 'Replace Image'; ?></label>
                <input type="file" id="primary_image" name="primary_image"
                       class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                <small class="field-help">Accepted: JPEG, PNG, GIF, WebP — max 5 MB.</small>
            </div>
        </div>

        <!-- ── Actions ───────────────────────────────────────────── -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Item</button>
            <a href="view.php?id=<?php echo urlencode($public_code); ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../templates/common/footer.php'; ?>
</body>
</html>