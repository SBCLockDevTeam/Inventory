<?php
/**
 * Items – Tree View
 *
 * Lists every item in a collapsed, hierarchical tree.
 * Not an admin-only function; accessible to all authenticated users.
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/tree_helper.php';

$tree       = TreeHelper::buildTree();
$view_base  = BASE_PATH . '/items/view.php?id=';
$page_title = 'Items';
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
<body>
    <?php include __DIR__ . '/../templates/common/header.php'; ?>
    <?php include __DIR__ . '/../templates/common/menu.php'; ?>
    <div id="error-division" class="error-banner" style="display: none;"></div>
    <h1>Items</h1>
    <div class="body-content">

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
            <a href="<?php echo BASE_PATH; ?>/items/add.php" class="btn btn-primary">+ Create New Item</a>
        </div>

    </div>
    <?php include __DIR__ . '/../templates/common/footer.php'; ?>