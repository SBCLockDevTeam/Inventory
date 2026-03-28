<?php
/**
 * Home Page
 * Shows all items in a collapsed hierarchical tree view.
 */
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/lib/database.php';
require_once __DIR__ . '/lib/tree_helper.php';
require_once __DIR__ . '/lib/client_helper.php';

$tree      = TreeHelper::buildTree();
$view_base = BASE_PATH . '/admin/items/view.php?id=';

$page_title = 'QR Inventory System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>components/tree.css">
    <script src="<?php echo JS_PATH; ?>script.js" defer></script>
    <script src="<?php echo JS_PATH; ?>pages/item_tree.js" defer></script>
</head>
<body data-base-path="<?php echo BASE_PATH; ?>">
    <?php include __DIR__ . '/templates/common/header.php'; ?>
    <?php include __DIR__ . '/templates/common/menu.php'; ?>
    <div class="body-content">
        <h1>QR Inventory</h1>

        <!-- Live-filter input -->
        <div class="tree-filter-section">
            <div class="tree-filter-form">
                <input type="text" id="tree-filter-input"
                       placeholder="Filter items by name…"
                       aria-label="Filter items">
            </div>
        </div>

        <!-- Expand / Collapse all -->
        <div class="tree-controls">
            <button id="tree-expand-all"   class="btn btn-secondary">Expand All</button>
            <button id="tree-collapse-all" class="btn btn-secondary">Collapse All</button>
        </div>

        <!-- Hierarchical tree -->
        <?php echo TreeHelper::renderTree($tree, $view_base); ?>

        <div class="actions-bottom">
            <a href="<?php echo BASE_PATH; ?>/admin/items/add.php" class="btn btn-primary">+ Add Item</a>
        </div>
    </div>
    <?php include __DIR__ . '/templates/common/footer.php'; ?>
