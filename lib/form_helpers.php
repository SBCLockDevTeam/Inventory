<?php
/**
 * FormHelper
 * 
 * Provides form input sanitization, validation, and helper methods
 * All inputs sanitized and escaped to prevent XSS/injection attacks
 */

class FormHelper {
    
    /**
     * Get POST value with optional default
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function getPost($key, $default = '') {
        return isset($_POST[$key]) ? self::sanitize($_POST[$key]) : $default;
    }

    /**
     * Get GET value with optional default
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function getGet($key, $default = '') {
        return isset($_GET[$key]) ? self::sanitize($_GET[$key]) : $default;
    }

    /**
     * Sanitize input to prevent XSS
     * @param mixed $input
     * @return string
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Check if value is required (not empty)
     * @param mixed $value
     * @return bool
     */
    public static function isRequired($value) {
        return !empty($value) && strlen(trim((string)$value)) > 0;
    }

    /**
     * Validate 10 hex character string
     * @param string $value
     * @return bool
     */
    public static function isValidHex10($value) {
        return preg_match('/^[0-9a-fA-F]{10}$/', trim($value)) === 1;
    }

    /**
     * Validate email
     * @param string $value
     * @return bool
     */
    public static function isValidEmail($value) {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate integer
     * @param string $value
     * @return bool
     */
    public static function isValidInt($value) {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate numeric
     * @param string $value
     * @return bool
     */
    public static function isValidNumeric($value) {
        return is_numeric($value);
    }
}
?>
