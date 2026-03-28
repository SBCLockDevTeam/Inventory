<?php
/**
 * Items List – legacy redirect
 *
 * The items listing is no longer an admin-only function.
 * Redirect permanently to the new public-facing tree view.
 */
require_once __DIR__ . '/../../config/settings.php';
header('Location: ' . BASE_PATH . '/items/', true, 301);
exit;