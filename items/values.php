<?php
/**
 * Item Field Values – redirects to the unified Edit Item page.
 * Field value editing has been merged into edit.php.
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/form_helpers.php';

$item_id = FormHelper::getGet('id');
$target  = FormHelper::isValidHex10($item_id)
    ? BASE_PATH . '/items/edit.php?id=' . rawurlencode($item_id)
    : BASE_PATH . '/items/';

header('Location: ' . $target);
exit;
