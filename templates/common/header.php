<?php
/**
 * Header Template
 *
 * Outputs DOCTYPE, <html>, <head> and the top <header> bar.
 * Sets $config and $rootUrl for use by subsequent includes (menu.php etc.).
 *
 * CSS loaded here: style.css (base), layout.css (page structure), responsive.css (breakpoints).
 * JavaScript is loaded at bottom of page in footer.php.
 */

// Use the singleton from bootstrap; never re-include settings.php directly.
$config  = app_settings();
$rootUrl = $config['root_url'] ?? '/qr/';

$appName = htmlspecialchars($config['app_name'] ?? 'SBC Inventory', ENT_QUOTES, 'UTF-8');
$tagline = htmlspecialchars($config['tagline']  ?? 'Security Building Controls', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $appName; ?></title>

    <!-- Base styles loaded in dependency order -->
    <link rel="stylesheet" href="/qr/css/style.css">
    <link rel="stylesheet" href="/qr/css/layout.css">
    <link rel="stylesheet" href="/qr/css/responsive.css">
</head>
<body>

<header class="main-header">
    <div class="header-container">
        <div class="logo">
            <h1><?php echo $appName; ?></h1>
            <p class="tagline"><?php echo $tagline; ?></p>
        </div>
        <!-- Brand selector: session-based; drives branding until authentication is added -->
        <div class="header-actions">
            <select id="brand-selector" class="brand-selector" aria-label="Select brand">
                <option value="sbc">Security Building Controls</option>
                <option value="other">Other Brand</option>
            </select>
        </div>
    </div>
</header>
