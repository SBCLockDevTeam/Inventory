<?php
/**
 * ClientHelper
 *
 * Manages client and user resolution with session persistence.
 *
 * With Microsoft Entra ID authentication enabled, the active user is
 * determined by the authenticated session (set by AuthHelper after login).
 * The active client is the one associated with that user in the database.
 * Admin users may override the active client via the header dropdown.
 *
 * The manual user-selector dropdown is no longer shown; users log in via
 * Entra ID and the system resolves their identity automatically.
 */

require_once __DIR__ . '/../lib/auth_helper.php';

class ClientHelper {

    const SESSION_CLIENT_KEY = 'selected_client_id';
    // SESSION_USER_KEY is kept for backward compatibility but no longer written by this class
    const SESSION_USER_KEY   = 'selected_user_id';

    /**
     * Start (or resume) the PHP session once per request.
     * Safe to call multiple times.
     */
    public static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // =========================================================
    // CLIENTS
    // =========================================================

    /**
     * Get all available clients from the database.
     *
     * @return array Rows of ['id', 'name', 'description', 'is_default']
     */
    public static function getAllClients(): array {
        return DatabaseHelper::queryAll(
            "SELECT id, name, description, is_default FROM clients ORDER BY name",
            []
        );
    }

    /**
     * Get the currently active client.
     *
     * For regular (non-admin) users, this is always the client they belong to.
     * For admin users, they may switch clients via the header dropdown; the
     * selected client is persisted in the session under SESSION_CLIENT_KEY.
     *
     * Falls back to the authenticated user's own client when no override is set.
     *
     * @return array|null Client row or null if none can be determined
     */
    public static function getActiveClient(): ?array {
        self::initSession();

        $auth_user = AuthHelper::getAuthUser();

        if ($auth_user) {
            // Admins may have a session-selected client override
            if (!empty($auth_user['is_admin'])) {
                $override_id = $_SESSION[self::SESSION_CLIENT_KEY] ?? null;
                if ($override_id !== null) {
                    $client = DatabaseHelper::queryOne(
                        "SELECT id, name, description, is_default FROM clients WHERE id = ?",
                        [(int)$override_id]
                    );
                    if ($client) {
                        return $client;
                    }
                    // Override no longer valid — fall through to user's own client
                    unset($_SESSION[self::SESSION_CLIENT_KEY]);
                }
            }

            // Return the client the authenticated user belongs to
            return DatabaseHelper::queryOne(
                "SELECT id, name, description, is_default FROM clients WHERE id = ?",
                [(int)$auth_user['client_id']]
            );
        }

        // No authenticated user — legacy fallback (used during tests or before auth is active)
        $client_id = $_SESSION[self::SESSION_CLIENT_KEY] ?? null;

        if ($client_id !== null) {
            $client = DatabaseHelper::queryOne(
                "SELECT id, name, description, is_default FROM clients WHERE id = ?",
                [(int)$client_id]
            );
            if ($client) {
                return $client;
            }
        }

        $client = DatabaseHelper::queryOne(
            "SELECT id, name, description, is_default FROM clients WHERE is_default = 1 LIMIT 1",
            []
        );

        if (!$client) {
            $client = DatabaseHelper::queryOne(
                "SELECT id, name, description, is_default FROM clients ORDER BY name LIMIT 1",
                []
            );
        }

        if ($client) {
            $_SESSION[self::SESSION_CLIENT_KEY] = (int)$client['id'];
        }

        return $client ?: null;
    }

    /**
     * Override the active client in the session (admin users only).
     * Regular users are always locked to their own client.
     *
     * @param int $client_id
     * @return bool True if the client exists and was set
     */
    public static function setActiveClient(int $client_id): bool {
        self::initSession();

        // Only admins may switch clients
        $auth_user = AuthHelper::getAuthUser();
        if ($auth_user && empty($auth_user['is_admin'])) {
            return false;
        }

        $client = DatabaseHelper::queryOne(
            "SELECT id FROM clients WHERE id = ?",
            [$client_id]
        );
        if (!$client) {
            return false;
        }
        $_SESSION[self::SESSION_CLIENT_KEY] = (int)$client['id'];
        return true;
    }

    // =========================================================
    // USERS
    // =========================================================

    /**
     * Get all users belonging to a given client.
     *
     * @param int $client_id
     * @return array Rows of ['id', 'name', 'email', 'is_default', 'is_admin', 'client_id']
     */
    public static function getAllUsersForClient(int $client_id): array {
        return DatabaseHelper::queryAll(
            "SELECT id, client_id, name, email, is_default, is_admin
               FROM users WHERE client_id = ? ORDER BY name",
            [$client_id]
        );
    }

    /**
     * Return the currently authenticated user as a user row.
     *
     * When Entra ID auth is active, the auth session is the authoritative source.
     * Returns null when no user is authenticated.
     *
     * @return array|null User row or null
     */
    public static function getActiveUser(): ?array {
        $auth_user = AuthHelper::getAuthUser();
        if ($auth_user) {
            // Return a shape consistent with the old DB row format
            return [
                'id'         => $auth_user['user_id'],
                'client_id'  => $auth_user['client_id'],
                'name'       => $auth_user['name'],
                'email'      => $auth_user['email'] ?? '',
                'is_default' => 0,
                'is_admin'   => (int)$auth_user['is_admin'],
            ];
        }
        return null;
    }

    /**
     * Return true if the currently active (authenticated) user has admin privileges.
     *
     * @return bool
     */
    public static function isActiveUserAdmin(): bool {
        $auth_user = AuthHelper::getAuthUser();
        return $auth_user && !empty($auth_user['is_admin']);
    }

    /**
     * Set the active user in the session.
     *
     * This method is retained for API compatibility but is a no-op when Entra
     * ID authentication is active (the logged-in user cannot impersonate others).
     *
     * @param int $user_id
     * @return bool Always false when auth is active; true only in the legacy path
     */
    public static function setActiveUser(int $user_id): bool {
        // Prevent user impersonation while authenticated via Entra ID
        if (AuthHelper::isAuthenticated()) {
            return false;
        }

        // Legacy fallback for unauthenticated context (e.g., dev/test without Entra)
        self::initSession();
        $active_client = self::getActiveClient();
        if (!$active_client) {
            return false;
        }
        $user = DatabaseHelper::queryOne(
            "SELECT id FROM users WHERE id = ? AND client_id = ?",
            [$user_id, (int)$active_client['id']]
        );
        if (!$user) {
            return false;
        }
        $_SESSION[self::SESSION_USER_KEY] = (int)$user['id'];
        return true;
    }
}

