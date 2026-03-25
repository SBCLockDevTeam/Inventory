<?php
/**
 * Add New Item Page
 * Supports both fresh item creation and cloning from an existing item.
 */

session_start();
require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/item_helpers.php';
require_once __DIR__ . '/../../lib/field_helpers.php';
require_once __DIR__ . '/../../lib/image_helpers.php';

$db = db();

$errors       = [];
$clone_source = null;

// ── Clone source ────────────────────────────────────────────────────────────
if (!empty($_GET['clone'])) {
    $clone_source = getItemWithFields($db, trim($_GET['clone']));
    if (!$clone_source) {
        $errors[] = 'Clone source item not found.';
    }
}

// ── Dropdown data ────────────────────────────────────────────────────────────
$brands     = getBrands($db);
$containers = getContainers($db);

// ── POST handling ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name             = trim($_POST['name']             ?? '');
    $description      = trim($_POST['description']      ?? '');
    $brand_id         = (int)($_POST['brand_id']        ?? 0);
    $location_item_id = trim($_POST['location_item_id'] ?? '');
    $is_container     = !empty($_POST['is_container'])  ? 1 : 0;

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
    } else {
        $parent = queryOne($db,
            'SELECT public_code, is_container FROM items WHERE public_code = ?',
            [$location_item_id]
        );
        if (!$parent) {
            $errors[] = 'Selected parent location does not exist.';
        } elseif (!(int)$parent['is_container']) {
            $errors[] = 'Selected parent location is not a container.';
        }
    }

    // Image validation (optional)
    $upload_error = null;
    if (!empty($_FILES['primary_image']['name'])) {
        if ($_FILES['primary_image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed (error code ' . (int)$_FILES['primary_image']['error'] . ').';
        }
    }

    if (empty($errors)) {
        try {
            beginTransaction($db);

            // Handle image upload
            $image_path = null;
            if (!empty($_FILES['primary_image']['name']) &&
                $_FILES['primary_image']['error'] === UPLOAD_ERR_OK) {
                $image_path = handleImageUpload($_FILES['primary_image']);
            }

            // Create item
            $public_code = createItem($db, [
                'name'             => $name,
                'description'      => $description !== '' ? $description : null,
                'brand_id'         => $brand_id,
                'location_item_id' => $location_item_id,
                'is_container'     => $is_container,
                'primary_image'    => $image_path,
            ]);

            // Clone custom fields (with values) from source if requested
            if ($clone_source) {
                cloneItemFields($db, $clone_source['public_code'], $public_code);
            }

            commit($db);
            logActivity("Item created: {$name} (Code: {$public_code})");

            add_error('notice', 'Item "' . $name . '" created successfully.');
            header('Location: view.php?id=' . urlencode($public_code) . '&created=1');
            exit;

        } catch (Exception $e) {
            rollback($db);
            log_exception($e, 'item_creation');
            $errors[] = 'Failed to create item: ' . $e->getMessage();
        }
    }
}

$page_title = $clone_source
    ? 'Clone Item: ' . htmlspecialchars($clone_source['name'])
    : 'Add New Item';
?>
<?php include __DIR__ . '/../../templates/common/header.php'; ?>
<?php include __DIR__ . '/../../templates/common/menu.php'; ?>

<div class="container">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <?php if (!empty($errors)): ?>
        <div class="error-banner">
            <?php foreach ($errors as $e): ?>
                <p class="error"><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($clone_source): ?>
        <div class="info-banner">
            <p>Cloning from: <strong><?php echo htmlspecialchars($clone_source['name']); ?></strong>
            — all custom fields and their values will be copied automatically.</p>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="item-form" novalidate>
        <?php if ($clone_source): ?>
            <input type="hidden" name="clone_source" value="<?php echo htmlspecialchars($clone_source['public_code']); ?>">
        <?php endif; ?>

        <!-- ── Core Fields ───────────────────────────────────────── -->
        <div class="form-section">
            <h2>Item Details</h2>

            <div class="form-group">
                <label for="name">Item Name <span class="required-star">*</span></label>
                <input type="text" id="name" name="name" class="form-control"
                       maxlength="255" required
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ($clone_source['name'] ?? '')); ?>">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4"><?php
                    echo htmlspecialchars($_POST['description'] ?? ($clone_source['description'] ?? ''));
                ?></textarea>
            </div>

            <div class="form-group">
                <label for="brand_id">Brand <span class="required-star">*</span></label>
                <select id="brand_id" name="brand_id" class="form-control" required>
                    <option value="">-- Select Brand --</option>
                    <?php
                    $selected_brand = (int)($_POST['brand_id'] ?? $clone_source['brand_id'] ?? 0);
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
                    $selected_loc = $_POST['location_item_id'] ?? ($clone_source['location_item_id'] ?? '');
                    foreach ($containers as $c):
                        $sel = $selected_loc === $c['public_code'] ? 'selected' : '';
                    ?>
                        <option value="<?php echo htmlspecialchars($c['public_code']); ?>" <?php echo $sel; ?>>
                            <?php echo htmlspecialchars($c['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="field-help">Only container items are listed.</small>
            </div>

            <div class="form-group form-group-inline">
                <input type="checkbox" id="is_container" name="is_container" value="1"
                    <?php echo !empty($_POST['is_container']) || !empty($clone_source['is_container']) ? 'checked' : ''; ?>>
                <label for="is_container">This item is a container (can hold other items)</label>
            </div>
        </div>

        <!-- ── Image Upload ──────────────────────────────────────── -->
        <div class="form-section">
            <h2>Primary Image</h2>

            <div class="form-group">
                <label for="primary_image">Upload Image</label>
                <input type="file" id="primary_image" name="primary_image"
                       class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                <small class="field-help">Accepted: JPEG, PNG, GIF, WebP — max 5 MB.</small>
            </div>
        </div>

        <!-- ── Actions ───────────────────────────────────────────── -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?php echo $clone_source ? 'Clone Item' : 'Add Item'; ?>
            </button>
            <a href="/public/inventory.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../templates/common/footer.php'; ?>
</body>
</html>
