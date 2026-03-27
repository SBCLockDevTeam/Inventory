<?php
/**
 * BrandHelper
 *
 * Manages brand selection and session persistence.
 * Brand is a theming/authentication stub — it has no relation to inventory items.
 * Selecting a brand changes only the page header, footer, and stylesheet.
 */
class BrandHelper {

    const SESSION_KEY = 'selected_brand_id';

    /**
     * Start (or resume) the PHP session once per request.
     * Safe to call multiple times.
     */
    public static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Get all available brands from the database.
     *
     * @return array Rows of ['id', 'name', 'description', 'is_default']
     */
    public static function getAllBrands(): array {
        return DatabaseHelper::queryAll(
            "SELECT id, name, description, is_default FROM brands ORDER BY name",
            []
        );
    }

    /**
     * Get the currently selected brand from the session.
     * Falls back to the default brand (is_default=1) or the first brand if no default is set.
     * Stores the resolved brand_id in the session for subsequent requests.
     *
     * @return array|null Brand row or null if no brands exist
     */
    public static function getActiveBrand(): ?array {
        self::initSession();
        $brand_id = $_SESSION[self::SESSION_KEY] ?? null;

        if ($brand_id !== null) {
            $brand = DatabaseHelper::queryOne(
                "SELECT id, name, description, is_default FROM brands WHERE id = ?",
                [(int)$brand_id]
            );
            if ($brand) {
                return $brand;
            }
        }

        // No valid session brand — load the default brand
        $brand = DatabaseHelper::queryOne(
            "SELECT id, name, description, is_default FROM brands WHERE is_default = 1 LIMIT 1",
            []
        );

        if (!$brand) {
            // No default set — use the first brand alphabetically
            $brand = DatabaseHelper::queryOne(
                "SELECT id, name, description, is_default FROM brands ORDER BY name LIMIT 1",
                []
            );
        }

        if ($brand) {
            $_SESSION[self::SESSION_KEY] = (int)$brand['id'];
        }

        return $brand ?: null;
    }

    /**
     * Set the active brand in the session.
     *
     * @param int $brand_id The brand ID to activate
     * @return bool True if the brand exists and was set, false otherwise
     */
    public static function setActiveBrand(int $brand_id): bool {
        self::initSession();
        $brand = DatabaseHelper::queryOne(
            "SELECT id FROM brands WHERE id = ?",
            [$brand_id]
        );
        if (!$brand) {
            return false;
        }
        $_SESSION[self::SESSION_KEY] = (int)$brand['id'];
        return true;
    }
}
