<?php
/**
 * OAuth2 callback handler — Microsoft Entra ID redirects here after login.
 *
 * Expected GET params:
 *   code  — authorization code
 *   state — CSRF state token (must match the value stored in session)
 *
 * On success: stores auth state in session and redirects the user to the page
 * they originally requested (or to the home page).
 *
 * On failure: renders a plain error page so the user knows what happened.
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/auth_helper.php';

// Microsoft may return an error (e.g. user cancelled login)
if (isset($_GET['error'])) {
    $err_msg = htmlspecialchars($_GET['error_description'] ?? $_GET['error'], ENT_QUOTES, 'UTF-8');
    showError('Microsoft login was not completed: ' . $err_msg);
}

$code  = trim($_GET['code']  ?? '');
$state = trim($_GET['state'] ?? '');

if (!$code || !$state) {
    showError('Missing required parameters from Microsoft. Please try logging in again.');
}

$result = AuthHelper::handleCallback($code, $state);

if ($result['success']) {
    header('Location: ' . $result['redirect']);
    exit;
}

showError(htmlspecialchars($result['error'], ENT_QUOTES, 'UTF-8'));

/**
 * Render a simple error page and stop execution.
 *
 * @param string $message HTML-safe error message
 */
function showError(string $message): never {
    require_once __DIR__ . '/../config/settings.php';
    $login_url = BASE_PATH . '/auth/login.php';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Error — QR Inventory</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <style>
        .auth-error-wrap { max-width: 520px; margin: 80px auto; padding: 2rem; text-align: center; }
        .auth-error-wrap h1 { margin-bottom: 1rem; }
        .auth-error-wrap p  { margin-bottom: 1.5rem; color: #555; }
    </style>
</head>
<body>
    <div class="auth-error-wrap">
        <h1>Login Failed</h1>
        <p><?php echo $message; ?></p>
        <a href="<?php echo htmlspecialchars($login_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">Try Again</a>
    </div>
</body>
</html>
    <?php
    exit;
}
