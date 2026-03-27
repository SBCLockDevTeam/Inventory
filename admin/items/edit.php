<?php
/**
 * Edit Item - update item details and/or move it to a different location.
 * Prevents circular references: an item cannot be placed inside one of its own descendants.
 */
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/form_helpers.php';
require_once __DIR__ . '/../../lib/location_helper.php';
require_once __DIR__ . '/../../lib/client_helper.php';

$item_id = FormHelper::getGet('id');
if (!FormHelper::isValidHex10($item_id)) {
    header('Location: ' . BASE_PATH . '/admin/items/');
    exit;
}

$item = DatabaseHelper::queryOne(
    "SELECT public_code, name, description, is_container, location_item_id
       FROM items
      WHERE public_code = ?",
    [$item_id]
);

if (!$item) {
    header('Location: ' . BASE_PATH . '/admin/items/');
    exit;
}

$errors  = [];
$success = false;

$active_user_is_admin = ClientHelper::isActiveUserAdmin();

// Seed form fields from existing item
$name             = $item['name'];
$description      = $item['description'];
$is_container     = $item['is_container'];
$location_item_id = $item['location_item_id'];
$is_root          = ($item['location_item_id'] === $item['public_code']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        // If the item is not already a root item, enforce one-root-per-client
        if (!$is_root) {
            $active_client_for_check = ClientHelper::getActiveClient();
            $client_id_check = $active_client_for_check ? (int)$active_client_for_check['id'] : null;
            if ($client_id_check !== null) {
                $existing_root = DatabaseHelper::queryOne(
                    "SELECT public_code FROM items WHERE client_id = ? AND location_item_id = public_code AND public_code != ? LIMIT 1",
                    [$client_id_check, $item_id]
                );
                if ($existing_root) {
                    $errors[] = 'This client already has a root item (' . htmlspecialchars($existing_root['public_code']) . '). Each client may only have one root item.';
                }
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
            $success          = true;
            $location_item_id = $new_parent;
            $is_root          = ($new_parent === $item_id);
        } else {
            $errors[] = 'Database update failed: ' . DatabaseHelper::getLastError();
        }
    }
}

// Exclude the item itself and all its descendants from the parent dropdown
$descendants_to_exclude   = LocationHelper::getDescendantCodes($item_id);
$descendants_to_exclude[] = $item_id;
$active_client            = ClientHelper::getActiveClient();
$available_containers     = LocationHelper::getAllContainers($descendants_to_exclude, $active_client ? (int)$active_client['id'] : null);

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
            <p class="success">Item updated successfully!</p>
        </div>
    <?php endif; ?>

    <h1>Edit Item</h1>

    <div class="body-content">

        <!-- Current location breadcrumb -->
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

        <form method="POST" action="" class="form-create-item">
            <div class="form-group">
                <label>Item ID</label>
                <input type="text" value="<?php echo htmlspecialchars($item['public_code']); ?>" disabled>
                <small>Item ID cannot be changed after creation</small>
            </div>
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
                <label for="location_item_id">Parent Location <?php echo !$active_user_is_admin ? '<span class="required">*</span>' : ''; ?></label>
                <select id="location_item_id" name="location_item_id"<?php echo !$active_user_is_admin ? ' required' : ''; ?>>
                    <?php if ($active_user_is_admin): ?>
                    <option value="root" <?php echo $is_root ? 'selected' : ''; ?>>
                        — No parent (Root item) —
                    </option>
                    <?php endif; ?>
                    <?php foreach ($available_containers as $container): ?>
                        <option value="<?php echo htmlspecialchars($container['public_code']); ?>"
                            <?php echo (!$is_root && $location_item_id === $container['public_code']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($container['name']); ?>
                            (<?php echo htmlspecialchars($container['public_code']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="location-selector-hint">
                    <?php if ($active_user_is_admin): ?>
                    Choose the container this item lives in, or leave as Root to make it a top-level item.
                    The item itself and its descendants are excluded from this list.
                    <?php else: ?>
                    Choose the container this item lives in. Only admin users may make an item a root item.
                    The item itself and its descendants are excluded from this list.
                    <?php endif; ?>
                </small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="<?php echo BASE_PATH; ?>/admin/items/view.php?id=<?php echo $item['public_code']; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>

    </div>
    <?php include __DIR__ . '/../../templates/common/footer.php'; ?>
