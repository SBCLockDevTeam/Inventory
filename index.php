<?php
declare(strict_types=1);

/**
 * Entry point for the SBC QR Inventory application.
 *
 * Apache document root: /var/www/html/sbcqr/
 * This file lives at:   /var/www/html/sbcqr/qr/index.php
 * Public URL:           https://SBCQR.com/qr/
 */

// ── 1. Bootstrap ────────────────────────────────────────────────────────────
require_once __DIR__ . '/lib/bootstrap.php';

$settings = app_settings();

// ── 2. Define BASE_URL constant used by all templates ───────────────────────
define('BASE_URL', rtrim($settings['base_url'], '/'));

// ── 3. Session ───────────────────────────────────────────────────────────────
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ── 4. Ensure uploads directory exists ──────────────────────────────────────
$uploadDir = $settings['upload_path'];
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
}

// ── 5. Route ────────────────────────────────────────────────────────────────
$requestUri  = $_SERVER['REQUEST_URI'] ?? '/';
$basePath    = parse_url(BASE_URL, PHP_URL_PATH); // '/qr'
$logicalPath = '/' . ltrim(substr($requestUri, strlen($basePath)), '/');
$logicalPath = strtok($logicalPath, '?');

// Note: no /scan route — QR codes embed the item URL directly (/qr/item?id=...).
$routes = [
    '/'           => __DIR__ . '/pages/dashboard.php',
    '/dashboard'  => __DIR__ . '/pages/dashboard.php',
    '/item'       => __DIR__ . '/pages/item.php',
    '/admin'      => __DIR__ . '/pages/admin.php',
    '/compliance' => __DIR__ . '/pages/compliance.php',
];

$page = $routes[$logicalPath] ?? null;

if ($page !== null && file_exists($page)) {
    require $page;
} else {
    http_response_code(404);
    $pageTitle = '404 – Page Not Found';
    $brand     = [];
    $brands    = [];
    include __DIR__ . '/templates/common/header.php';
    include __DIR__ . '/templates/common/menu.php';
    echo '<main class="main-content"><div class="container"><h1>404 – Page Not Found</h1>';
    echo '<p><a href="' . BASE_URL . '/">Return to Dashboard</a></p></div></main>';
    include __DIR__ . '/templates/common/footer.php';
}