<?php
/**
 * AuthHelper
 *
 * Handles Microsoft Entra ID (Azure AD) authentication using
 * OAuth 2.0 Authorization Code Flow with PKCE.
 *
 * Credentials (tenant ID, client ID, client secret) are loaded
 * from /config/secrets.php and must never be committed to Git.
 *
 * Typical page flow:
 *   1. Page calls AuthHelper::requireAuth() near the top.
 *   2. If not authenticated, the user is redirected to auth/login.php.
 *   3. login.php calls AuthHelper::initiateLogin(), which redirects to Microsoft.
 *   4. Microsoft redirects to auth/callback.php with ?code=...&state=...
 *   5. callback.php calls AuthHelper::handleCallback(), which exchanges the code,
 *      resolves the local user, and stores auth state in $_SESSION.
 *   6. The user is redirected back to the page they originally requested.
 */

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/database.php';

class AuthHelper {

    const SESSION_AUTH_KEY     = 'entra_auth';
    const SESSION_STATE_KEY    = 'oauth_state';
    const SESSION_VERIFIER_KEY = 'oauth_code_verifier';
    const SESSION_RETURN_KEY   = 'oauth_return_url';

    // =========================================================
    // Public API
    // =========================================================

    /**
     * Return true when the current session has a verified Entra auth record.
     *
     * @return bool
     */
    public static function isAuthenticated(): bool {
        self::initSession();
        return !empty($_SESSION[self::SESSION_AUTH_KEY]);
    }

    /**
     * Return the authenticated user data stored in the session.
     *
     * Keys returned:
     *   entra_oid, email, display_name, user_id (int), client_id (int),
     *   name (string), is_admin (bool)
     *
     * Returns null when not authenticated.
     *
     * @return array|null
     */
    public static function getAuthUser(): ?array {
        self::initSession();
        return $_SESSION[self::SESSION_AUTH_KEY] ?? null;
    }

    /**
     * Redirect to the login page if the visitor is not authenticated.
     * Saves the current URL so the login flow can return the user here.
     * Call this near the top of every protected page (or in header.php).
     *
     * @return void
     */
    public static function requireAuth(): void {
        if (!self::isAuthenticated()) {
            self::initSession();
            // Save the originally requested URL for post-login redirect
            $_SESSION[self::SESSION_RETURN_KEY] = $_SERVER['REQUEST_URI'] ?? (BASE_PATH . '/home.php');
            header('Location: ' . BASE_PATH . '/auth/login.php');
            exit;
        }
    }

    /**
     * Generate a PKCE code challenge, store the verifier and a CSRF state
     * token in the session, then redirect the browser to Microsoft's
     * /authorize endpoint.
     *
     * @return void  (exits after redirect)
     */
    public static function initiateLogin(): void {
        self::initSession();

        $state          = self::generateRandomString(32);
        $code_verifier  = self::generateRandomString(48); // 48 bytes → 96 hex chars — well within RFC 7636 limits
        $code_challenge = self::generateCodeChallenge($code_verifier);

        $_SESSION[self::SESSION_STATE_KEY]    = $state;
        $_SESSION[self::SESSION_VERIFIER_KEY] = $code_verifier;

        $config = self::getConfig();
        $params = http_build_query([
            'client_id'             => $config['client_id'],
            'response_type'         => 'code',
            'redirect_uri'          => $config['redirect_uri'],
            'scope'                 => 'openid profile email',
            'response_mode'         => 'query',
            'state'                 => $state,
            'code_challenge'        => $code_challenge,
            'code_challenge_method' => 'S256',
        ]);

        $auth_url = 'https://login.microsoftonline.com/'
                  . rawurlencode($config['tenant_id'])
                  . '/oauth2/v2.0/authorize?' . $params;

        header('Location: ' . $auth_url);
        exit;
    }

    /**
     * Handle the OAuth 2.0 callback from Microsoft.
     *
     * Validates the CSRF state token, exchanges the authorization code for
     * tokens, extracts user claims from the ID token, looks up (or links)
     * the local user record, and stores auth state in the session.
     *
     * @param string $code  Authorization code returned by Microsoft
     * @param string $state State value returned by Microsoft
     * @return array ['success'=>true, 'redirect'=>string]
     *               or ['success'=>false, 'error'=>string]
     */
    public static function handleCallback(string $code, string $state): array {
        self::initSession();

        // Validate CSRF state to prevent open-redirect / CSRF attacks
        $expected_state = $_SESSION[self::SESSION_STATE_KEY] ?? '';
        if (!hash_equals($expected_state, $state)) {
            return ['success' => false, 'error' => 'Invalid OAuth state. Please try logging in again.'];
        }

        $code_verifier = $_SESSION[self::SESSION_VERIFIER_KEY] ?? '';
        unset($_SESSION[self::SESSION_STATE_KEY], $_SESSION[self::SESSION_VERIFIER_KEY]);

        // Exchange the code for tokens
        $tokens = self::exchangeCodeForTokens($code, $code_verifier);
        if (isset($tokens['error'])) {
            $desc = $tokens['error_description'] ?? $tokens['error'];
            return ['success' => false, 'error' => 'Token exchange failed: ' . $desc];
        }

        // Parse claims from the ID token (trusted: came directly from Microsoft)
        $claims = self::parseJwtPayload($tokens['id_token'] ?? '');
        if (empty($claims)) {
            return ['success' => false, 'error' => 'Could not read the identity token from Microsoft.'];
        }

        $entra_oid    = $claims['oid'] ?? '';
        // preferred_username is the UPN (usually the work email)
        $email        = strtolower(trim($claims['preferred_username'] ?? $claims['email'] ?? ''));
        $display_name = trim($claims['name'] ?? '');

        if (!$entra_oid || !$email) {
            return ['success' => false, 'error' => 'Microsoft did not return a user identifier. Please contact your administrator.'];
        }

        // Match the Microsoft identity to a local user record
        $local_user = self::resolveLocalUser($entra_oid, $email, $display_name);
        if (!$local_user) {
            return [
                'success' => false,
                'error'   => 'Your Microsoft account (' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8')
                           . ') is not registered in this system. '
                           . 'Please ask your administrator to add your email address to your user account.',
            ];
        }

        // Store auth state in session
        $_SESSION[self::SESSION_AUTH_KEY] = [
            'entra_oid'    => $entra_oid,
            'email'        => $email,
            'display_name' => $display_name,
            'user_id'      => (int)$local_user['id'],
            'client_id'    => (int)$local_user['client_id'],
            'name'         => $local_user['name'],
            'is_admin'     => (bool)$local_user['is_admin'],
        ];

        $return_url = $_SESSION[self::SESSION_RETURN_KEY] ?? (BASE_PATH . '/home.php');
        unset($_SESSION[self::SESSION_RETURN_KEY]);

        return ['success' => true, 'redirect' => $return_url];
    }

    /**
     * Clear the auth session entry and return the Microsoft single sign-out URL.
     * The caller should redirect the browser to this URL so Microsoft also
     * clears its SSO cookies.
     *
     * @return string Entra ID logout URL
     */
    public static function logout(): string {
        self::initSession();
        unset($_SESSION[self::SESSION_AUTH_KEY]);

        $config   = self::getConfig();
        $post_url = rawurlencode(BASE_URL . '/auth/login.php');

        return 'https://login.microsoftonline.com/'
             . rawurlencode($config['tenant_id'])
             . '/oauth2/v2.0/logout?post_logout_redirect_uri=' . $post_url;
    }

    // =========================================================
    // Private helpers
    // =========================================================

    /**
     * Start (or resume) the PHP session, safely called multiple times.
     */
    private static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Load Entra ID config from secrets.php once per request.
     *
     * @return array ['tenant_id', 'client_id', 'client_secret', 'redirect_uri']
     */
    private static function getConfig(): array {
        static $config = null;
        if ($config === null) {
            require_once SERVER_ROOT . '/config/secrets.php';
            $config = [
                'tenant_id'     => ENTRA_TENANT_ID,
                'client_id'     => ENTRA_CLIENT_ID,
                'client_secret' => ENTRA_CLIENT_SECRET,
                'redirect_uri'  => BASE_URL . '/auth/callback.php',
            ];
        }
        return $config;
    }

    /**
     * POST to Microsoft's token endpoint and return the decoded JSON response.
     * Uses PHP stream context (no Composer / cURL required).
     *
     * @param string $code          Authorization code from the callback
     * @param string $code_verifier PKCE verifier matching the earlier challenge
     * @return array Token response or ['error'=>..., 'error_description'=>...]
     */
    private static function exchangeCodeForTokens(string $code, string $code_verifier): array {
        $config = self::getConfig();
        $url    = 'https://login.microsoftonline.com/'
                . rawurlencode($config['tenant_id'])
                . '/oauth2/v2.0/token';

        $body = http_build_query([
            'client_id'     => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $config['redirect_uri'],
            'code_verifier' => $code_verifier,
            'scope'         => 'openid profile email',
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: application/x-www-form-urlencoded\r\n"
                                 . 'Content-Length: ' . strlen($body) . "\r\n",
                'content'       => $body,
                'timeout'       => 15,
                'ignore_errors' => true, // capture 4xx/5xx body
            ],
        ]);

        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) {
            return [
                'error'             => 'network_error',
                'error_description' => 'Could not reach the Microsoft token endpoint.',
            ];
        }

        $data = json_decode($response, true);
        return is_array($data)
            ? $data
            : ['error' => 'parse_error', 'error_description' => 'Unexpected response from Microsoft.'];
    }

    /**
     * Base64url-decode the payload section of a JWT and return the claims.
     *
     * We trust the payload because it was received directly from Microsoft's
     * /token endpoint over HTTPS, not supplied by the end user.
     *
     * @param string $token JWT string
     * @return array Decoded claims, or empty array on failure
     */
    private static function parseJwtPayload(string $token): array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return [];
        }

        // Base64url → standard base64 → decode
        $payload = base64_decode(strtr($parts[1], '-_', '+/'), true);
        if ($payload === false) {
            return [];
        }

        $claims = json_decode($payload, true);
        return is_array($claims) ? $claims : [];
    }

    /**
     * Find the local user record matching the Entra identity.
     *
     * Lookup order:
     *   1. By entra_oid (stable — used on every subsequent login).
     *   2. By email (first login after admin sets the email but before OID is stored).
     *      On match, the OID is persisted so future logins skip the email lookup.
     *
     * @param string $entra_oid  Microsoft object ID (sub/oid claim)
     * @param string $email      Normalised email address
     * @param string $display_name User's display name from Entra (informational only)
     * @return array|null Local user DB row or null if not found
     */
    private static function resolveLocalUser(string $entra_oid, string $email, string $display_name): ?array {
        // Fast path: OID already stored
        $user = DatabaseHelper::queryOne(
            "SELECT id, client_id, name, is_default, is_admin, email, entra_oid
               FROM users WHERE entra_oid = ?",
            [$entra_oid]
        );
        if ($user) {
            return $user;
        }

        // Slower path: match by email (admin pre-populated the email field)
        $user = DatabaseHelper::queryOne(
            "SELECT id, client_id, name, is_default, is_admin, email, entra_oid
               FROM users WHERE email = ?",
            [$email]
        );
        if ($user) {
            // Store the OID so future logins use the fast path
            DatabaseHelper::execute(
                "UPDATE users SET entra_oid = ? WHERE id = ?",
                [$entra_oid, (int)$user['id']]
            );
            return $user;
        }

        return null;
    }

    /**
     * Generate a cryptographically random URL-safe hex string.
     *
     * @param int $bytes Number of random bytes (output length = $bytes * 2)
     * @return string
     */
    private static function generateRandomString(int $bytes): string {
        return bin2hex(random_bytes($bytes));
    }

    /**
     * Derive a PKCE S256 code challenge from the verifier.
     *
     * code_challenge = BASE64URL( SHA-256( ASCII(code_verifier) ) )
     *
     * @param string $verifier Code verifier string
     * @return string Base64url-encoded challenge
     */
    private static function generateCodeChallenge(string $verifier): string {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }
}
