<?php
/**
 * ClientHelper
 *
 * Manages user resolution from the authenticated session.
 * The application is single-tenant — client management is removed.
 */

require_once __DIR__ . '/../lib/auth_helper.php';

class ClientHelper {

    /**
     * Return the currently authenticated user as a user row.
     * Returns null when no user is authenticated.
     *
     * @return array|null
     */
    public static function getActiveUser(): ?array {
        $auth_user = AuthHelper::getAuthUser();
        if ($auth_user) {
            return [
                'id'       => $auth_user['user_id'],
                'name'     => $auth_user['name'],
                'email'    => $auth_user['email'] ?? '',
                'is_admin' => (int)$auth_user['is_admin'],
            ];
        }
        return null;
    }

    /**
     * Return true if the currently authenticated user has admin privileges.
     *
     * @return bool
     */
    public static function isActiveUserAdmin(): bool {
        $auth_user = AuthHelper::getAuthUser();
        return $auth_user && !empty($auth_user['is_admin']);
    }
}
