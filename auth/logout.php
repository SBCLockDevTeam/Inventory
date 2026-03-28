<?php
/**
 * Logout page — clears the local auth session and redirects to Microsoft's
 * single sign-out endpoint so the Entra ID SSO session is also terminated.
 *
 * After Microsoft processes the logout it redirects back to auth/login.php
 * (as configured in ENTRA_TENANT's app registration).
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/auth_helper.php';

$logout_url = AuthHelper::logout();

header('Location: ' . $logout_url);
exit;
