<?php
/**
 * Login page — initiates the Microsoft Entra ID OAuth2/PKCE flow.
 *
 * If the user is already authenticated, they are redirected to the home page.
 * Otherwise, this page redirects to Microsoft's /authorize endpoint.
 *
 * No HTML is rendered; this is a redirect-only page.
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/auth_helper.php';

// Already logged in — send them home
if (AuthHelper::isAuthenticated()) {
    header('Location: ' . BASE_PATH . '/');
    exit;
}

AuthHelper::initiateLogin();
