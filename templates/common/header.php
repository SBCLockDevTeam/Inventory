<?php
/**
 * Common header template.
 * Included at the top of every page.
 * Expects $pageTitle (string) and $brand (array) to be set before inclusion.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SBC Inventory') ?> &mdash; SBC Inventory</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/layout.css">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <div class="header-brand">
            <?= htmlspecialchars($brand['name'] ?? 'SBC Inventory') ?>
        </div>
        <div class="header-brand-selector">
            <?php include __DIR__ . '/brand_selector.php'; ?>
        </div>
    </div>
</header>