<?php
// Form validation and sanitization helpers

/**
 * Sanitize a string by stripping out invalid characters.
 *
 * @param string $string The string to sanitize.
 * @return string The sanitized string.
 */
function sanitize_string($string) {
    return filter_var($string, FILTER_SANITIZE_STRING);
}

/**
 * Validates an email address.
 *
 * @param string $email The email address to validate.
 * @return bool True if valid, false otherwise.
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validates a URL.
 *
 * @param string $url The URL to validate.
 * @return bool True if valid, false otherwise.
 */
function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Validates a phone number based on basic criteria (length, etc).
 *
 * @param string $phone The phone number to validate.
 * @return bool True if valid, false otherwise.
 */
function validate_phone($phone) {
    // Example: Check if the phone number is numeric and has a length of 10
    return preg_match('/^[0-9]{10}$/', $phone);
}

?>