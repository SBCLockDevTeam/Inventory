<?php
/**
 * FieldHelper
 *
 * Reusable helpers for item dynamic fields: definitions, values, images,
 * documents, and signatures.
 *
 * Why a separate helper: field logic is used on both the values entry page
 * and the item view page; extracting it here keeps pages thin and
 * avoids duplicating SQL.
 */
class FieldHelper {

    // =========================================================
    // FIELD DEFINITIONS
    // =========================================================

    /**
     * Get all field definitions for an item, ordered by sort_order.
     *
     * @param string $item_public_code
     * @return array Rows of item_fields
     */
    public static function getFields(string $item_public_code): array {
        return DatabaseHelper::queryAll(
            "SELECT id, field_key, label, field_type, required, sort_order,
                    allow_multiple, instructions, require_printed_name, publicly_viewable
               FROM item_fields
              WHERE item_public_code = ?
              ORDER BY sort_order",
            [$item_public_code]
        );
    }

    // =========================================================
    // SCALAR FIELD VALUES (text / textarea / number / date / checkbox)
    // =========================================================

    /**
     * Get all scalar field values for an item, keyed by field_id.
     *
     * @param string $item_public_code
     * @return array [ field_id => row ]
     */
    public static function getScalarValues(string $item_public_code): array {
        $rows = DatabaseHelper::queryAll(
            "SELECT id, field_id, value_text, value_number, value_date, value_bool
               FROM item_field_values
              WHERE item_public_code = ?",
            [$item_public_code]
        );
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int)$row['field_id']] = $row;
        }
        return $indexed;
    }

    /**
     * Save (upsert) a scalar field value.
     * Uses INSERT … ON DUPLICATE KEY UPDATE to avoid race conditions.
     *
     * @param string $item_public_code
     * @param int    $field_id
     * @param string $field_type   One of text|textarea|number|date|checkbox
     * @param mixed  $raw_value    The raw POST value
     * @return bool
     */
    public static function saveScalarValue(
        string $item_public_code,
        int    $field_id,
        string $field_type,
        $raw_value
    ): bool {
        $value_text   = null;
        $value_number = null;
        $value_date   = null;
        $value_bool   = null;

        switch ($field_type) {
            case 'text':
            case 'textarea':
                $value_text = (string)$raw_value;
                break;
            case 'number':
                $value_number = is_numeric($raw_value) ? (float)$raw_value : null;
                break;
            case 'date':
                // Use strict DateTime parsing to reject invalid dates like 2026-02-31
                if ($raw_value !== '') {
                    $dt = DateTime::createFromFormat('Y-m-d', $raw_value);
                    $value_date = ($dt && $dt->format('Y-m-d') === $raw_value) ? $raw_value : null;
                }
                break;
            case 'checkbox':
                $value_bool = $raw_value ? 1 : 0;
                break;
        }

        $sql = "INSERT INTO item_field_values
                    (item_public_code, field_id, value_text, value_number, value_date, value_bool)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    value_text   = VALUES(value_text),
                    value_number = VALUES(value_number),
                    value_date   = VALUES(value_date),
                    value_bool   = VALUES(value_bool)";

        $affected = DatabaseHelper::execute(
            $sql,
            [$item_public_code, $field_id, $value_text, $value_number, $value_date, $value_bool]
        );

        return $affected >= 0;
    }

    // =========================================================
    // PHOTO VALUES
    // =========================================================

    /**
     * Get all stored photos for a given field on an item.
     *
     * @param string $item_public_code
     * @param int    $field_id
     * @return array Rows of item_images
     */
    public static function getPhotos(string $item_public_code, int $field_id): array {
        return DatabaseHelper::queryAll(
            "SELECT id, file_path, caption, sort_order
               FROM item_images
              WHERE item_public_code = ? AND field_id = ?
              ORDER BY sort_order",
            [$item_public_code, $field_id]
        );
    }

    /**
     * Get all photos for an item across all fields, keyed by field_id.
     *
     * @param string $item_public_code
     * @return array [ field_id => [rows] ]
     */
    public static function getAllPhotos(string $item_public_code): array {
        $rows = DatabaseHelper::queryAll(
            "SELECT id, field_id, file_path, caption, sort_order
               FROM item_images
              WHERE item_public_code = ?
              ORDER BY field_id, sort_order",
            [$item_public_code]
        );
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int)$row['field_id']][] = $row;
        }
        return $grouped;
    }

    /**
     * Save an uploaded photo record.
     *
     * @param string $item_public_code
     * @param int    $field_id
     * @param string $file_path   Server-relative URL path to the saved file
     * @param string $caption
     * @return int Inserted row ID or 0 on failure
     */
    public static function savePhoto(
        string $item_public_code,
        int    $field_id,
        string $file_path,
        string $caption = ''
    ): int {
        $sort_order = DatabaseHelper::queryCount(
            "SELECT COUNT(*) AS count FROM item_images WHERE item_public_code = ? AND field_id = ?",
            [$item_public_code, $field_id]
        );

        DatabaseHelper::execute(
            "INSERT INTO item_images (item_public_code, field_id, file_path, caption, sort_order)
             VALUES (?, ?, ?, ?, ?)",
            [$item_public_code, $field_id, $file_path, $caption, $sort_order]
        );

        return (int)DatabaseHelper::getLastInsertId();
    }

    /**
     * Delete a single photo record (and the physical file is removed by the caller).
     *
     * @param int    $image_id
     * @param string $item_public_code  Ownership check
     * @return bool
     */
    public static function deletePhoto(int $image_id, string $item_public_code): bool {
        $affected = DatabaseHelper::execute(
            "DELETE FROM item_images WHERE id = ? AND item_public_code = ?",
            [$image_id, $item_public_code]
        );
        return $affected > 0;
    }

    // =========================================================
    // DOCUMENT VALUES
    // =========================================================

    /**
     * Get all documents for a given field on an item.
     *
     * @param string $item_public_code
     * @param int    $field_id
     * @return array Rows of item_documents
     */
    public static function getDocuments(string $item_public_code, int $field_id): array {
        return DatabaseHelper::queryAll(
            "SELECT id, file_path, original_filename, mime_type
               FROM item_documents
              WHERE item_public_code = ? AND field_id = ?
              ORDER BY created_at",
            [$item_public_code, $field_id]
        );
    }

    /**
     * Get all documents for an item across all fields, keyed by field_id.
     *
     * @param string $item_public_code
     * @return array [ field_id => [rows] ]
     */
    public static function getAllDocuments(string $item_public_code): array {
        $rows = DatabaseHelper::queryAll(
            "SELECT id, field_id, file_path, original_filename, mime_type
               FROM item_documents
              WHERE item_public_code = ?
              ORDER BY field_id, created_at",
            [$item_public_code]
        );
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int)$row['field_id']][] = $row;
        }
        return $grouped;
    }

    /**
     * Save an uploaded document record.
     *
     * @param string $item_public_code
     * @param int    $field_id
     * @param string $file_path
     * @param string $original_filename
     * @param string $mime_type
     * @return int Inserted row ID or 0
     */
    public static function saveDocument(
        string $item_public_code,
        int    $field_id,
        string $file_path,
        string $original_filename,
        string $mime_type
    ): int {
        DatabaseHelper::execute(
            "INSERT INTO item_documents (item_public_code, field_id, file_path, original_filename, mime_type)
             VALUES (?, ?, ?, ?, ?)",
            [$item_public_code, $field_id, $file_path, $original_filename, $mime_type]
        );
        return (int)DatabaseHelper::getLastInsertId();
    }

    /**
     * Delete a document record.
     *
     * @param int    $doc_id
     * @param string $item_public_code  Ownership check
     * @return bool
     */
    public static function deleteDocument(int $doc_id, string $item_public_code): bool {
        $affected = DatabaseHelper::execute(
            "DELETE FROM item_documents WHERE id = ? AND item_public_code = ?",
            [$doc_id, $item_public_code]
        );
        return $affected > 0;
    }

    // =========================================================
    // SIGNATURE VALUES
    // =========================================================

    /**
     * Get all signatures for a given field on an item.
     *
     * @param string $item_public_code
     * @param int    $field_id
     * @return array Rows of item_signatures
     */
    public static function getSignatures(string $item_public_code, int $field_id): array {
        return DatabaseHelper::queryAll(
            "SELECT id, signature_image_path, printed_name, created_at
               FROM item_signatures
              WHERE item_public_code = ? AND field_id = ?
              ORDER BY created_at",
            [$item_public_code, $field_id]
        );
    }

    /**
     * Get all signatures for an item across all fields, keyed by field_id.
     *
     * @param string $item_public_code
     * @return array [ field_id => [rows] ]
     */
    public static function getAllSignatures(string $item_public_code): array {
        $rows = DatabaseHelper::queryAll(
            "SELECT id, field_id, signature_image_path, printed_name, created_at
               FROM item_signatures
              WHERE item_public_code = ?
              ORDER BY field_id, created_at",
            [$item_public_code]
        );
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int)$row['field_id']][] = $row;
        }
        return $grouped;
    }

    /**
     * Save a signature.
     *
     * @param string $item_public_code
     * @param int    $field_id
     * @param string $signature_image_path  Server-relative URL to saved PNG
     * @param string $printed_name
     * @return int Inserted row ID or 0
     */
    public static function saveSignature(
        string $item_public_code,
        int    $field_id,
        string $signature_image_path,
        string $printed_name = ''
    ): int {
        DatabaseHelper::execute(
            "INSERT INTO item_signatures (item_public_code, field_id, signature_image_path, printed_name)
             VALUES (?, ?, ?, ?)",
            [$item_public_code, $field_id, $signature_image_path, $printed_name]
        );
        return (int)DatabaseHelper::getLastInsertId();
    }

    /**
     * Delete a signature record.
     *
     * @param int    $sig_id
     * @param string $item_public_code  Ownership check
     * @return bool
     */
    public static function deleteSignature(int $sig_id, string $item_public_code): bool {
        $affected = DatabaseHelper::execute(
            "DELETE FROM item_signatures WHERE id = ? AND item_public_code = ?",
            [$sig_id, $item_public_code]
        );
        return $affected > 0;
    }

    // =========================================================
    // LOGGING HELPERS
    // =========================================================

    /**
     * Record an event in the general (admin-facing) log.
     *
     * @param string      $action_type    Short action label (e.g., 'field_value_updated')
     * @param string|null $item_code      Item public_code if applicable
     * @param int|null    $field_id       Field ID if applicable
     * @param string|null $before         Value before the change
     * @param string|null $after          Value after the change
     * @param string|null $notes          Extra context
     * @param string|null $user_identifier Human-readable user label
     */
    public static function logGeneral(
        string  $action_type,
        ?string $item_code       = null,
        ?int    $field_id        = null,
        ?string $before          = null,
        ?string $after           = null,
        ?string $notes           = null,
        ?string $user_identifier = null
    ): void {
        DatabaseHelper::execute(
            "INSERT INTO general_log
                 (user_identifier, action_type, item_public_code, field_id, value_before, value_after, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$user_identifier, $action_type, $item_code, $field_id, $before, $after, $notes]
        );
    }

    /**
     * Record a plain-language event in the exceptions (customer-facing) log.
     *
     * @param string      $event_summary  Plain-language description
     * @param string|null $item_code      Related item public_code, if applicable
     */
    public static function logException(string $event_summary, ?string $item_code = null): void {
        DatabaseHelper::execute(
            "INSERT INTO exceptions_log (item_public_code, event_summary) VALUES (?, ?)",
            [$item_code, $event_summary]
        );
    }
}
