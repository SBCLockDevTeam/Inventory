<?php
/**
 * Legacy redirect – this page has moved to /items/.
 */
require_once __DIR__ . '/../../config/settings.php';
$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: ' . BASE_PATH . '/items/values.php' . $query, true, 301);
exit;
