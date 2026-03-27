<?php
/**
 * ClientHelper
 *
 * Manages client and user selection with session persistence.
 * The header displays "Client Name - User Name".
 * Changing the selected user redirects the browser to the home page.
 */
class ClientHelper {

    const SESSION_CLIENT_KEY = 'selected_client_id';
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
     * Get the currently selected client from the session.
     * Falls back to the default client or the first client alphabetically.
     *
     * @return array|null Client row or null if no clients exist
     */
    public static function getActiveClient(): ?array {
        self::initSession();
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

        // No valid session client — load the default
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
            // Reset user when client changes
            unset($_SESSION[self::SESSION_USER_KEY]);
        }

        return $client ?: null;
    }

    /**
     * Set the active client in the session and clear the selected user.
     *
     * @param int $client_id
     * @return bool True if the client exists and was set
     */
    public static function setActiveClient(int $client_id): bool {
        self::initSession();
        $client = DatabaseHelper::queryOne(
            "SELECT id FROM clients WHERE id = ?",
            [$client_id]
        );
        if (!$client) {
            return false;
        }
        $_SESSION[self::SESSION_CLIENT_KEY] = (int)$client['id'];
        unset($_SESSION[self::SESSION_USER_KEY]);
        return true;
    }

    // =========================================================
    // USERS
    // =========================================================

    /**
     * Get all users belonging to a given client.
     *
     * @param int $client_id
     * @return array Rows of ['id', 'name', 'is_default', 'client_id']
     */
    public static function getAllUsersForClient(int $client_id): array {
        return DatabaseHelper::queryAll(
            "SELECT id, client_id, name, is_default FROM users WHERE client_id = ? ORDER BY name",
            [$client_id]
        );
    }

    /**
     * Get the currently selected user from the session.
     * Falls back to the default user for the active client, or the first user alphabetically.
     *
     * @return array|null User row or null if no users exist for the active client
     */
    public static function getActiveUser(): ?array {
        self::initSession();
        $active_client = self::getActiveClient();
        if (!$active_client) {
            return null;
        }
        $client_id = (int)$active_client['id'];

        $user_id = $_SESSION[self::SESSION_USER_KEY] ?? null;
        if ($user_id !== null) {
            $user = DatabaseHelper::queryOne(
                "SELECT id, client_id, name, is_default FROM users WHERE id = ? AND client_id = ?",
                [(int)$user_id, $client_id]
            );
            if ($user) {
                return $user;
            }
        }

        // No valid session user — load the default for this client
        $user = DatabaseHelper::queryOne(
            "SELECT id, client_id, name, is_default FROM users WHERE client_id = ? AND is_default = 1 LIMIT 1",
            [$client_id]
        );

        if (!$user) {
            $user = DatabaseHelper::queryOne(
                "SELECT id, client_id, name, is_default FROM users WHERE client_id = ? ORDER BY name LIMIT 1",
                [$client_id]
            );
        }

        if ($user) {
            $_SESSION[self::SESSION_USER_KEY] = (int)$user['id'];
        }

        return $user ?: null;
    }

    /**
     * Set the active user in the session.
     * The user must belong to the currently active client.
     *
     * @param int $user_id
     * @return bool True if the user exists under the active client and was set
     */
    public static function setActiveUser(int $user_id): bool {
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
