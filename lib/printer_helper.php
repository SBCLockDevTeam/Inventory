<?php
/**
 * PrinterHelper
 *
 * Utilities for loading active printers and resolving the currently
 * selected printer for the authenticated user.
 */

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/auth_helper.php';

class PrinterHelper {

    /**
     * Load all active printers from the database, ordered by sort_order then name.
     *
     * @return array  Array of printer rows: {id, name, is_default}
     */
    public static function getActivePrinters(): array {
        return DatabaseHelper::queryAll(
            "SELECT id, name, is_default FROM printers WHERE is_active = 1 ORDER BY sort_order, name",
            []
        );
    }

    /**
     * Determine the printer ID that should be pre-selected in the UI.
     *
     * Priority: (1) authenticated user's saved preference if still active,
     *           (2) system default printer, (3) first active printer.
     *
     * @param  array  $printers  The active printer list returned by getActivePrinters().
     * @return int               Printer ID, or 0 when no printers are available.
     */
    public static function getSelectedPrinterId(array $printers): int {
        if (empty($printers)) {
            return 0;
        }

        // Find the system default
        $default_id = 0;
        foreach ($printers as $p) {
            if ($p['is_default']) {
                $default_id = (int)$p['id'];
                break;
            }
        }
        // Fall back to the first active printer when no explicit default is set
        if ($default_id === 0) {
            $default_id = (int)$printers[0]['id'];
        }

        // Override with the authenticated user's saved preference (if still active)
        $auth_user = AuthHelper::getAuthUser();
        if ($auth_user && !empty($auth_user['user_id'])) {
            $user_pref = DatabaseHelper::queryOne(
                "SELECT preferred_printer_id FROM users WHERE id = ?",
                [(int)$auth_user['user_id']]
            );
            if ($user_pref && !empty($user_pref['preferred_printer_id'])) {
                foreach ($printers as $p) {
                    if ((int)$p['id'] === (int)$user_pref['preferred_printer_id']) {
                        return (int)$user_pref['preferred_printer_id'];
                    }
                }
            }
        }

        return $default_id;
    }
}
