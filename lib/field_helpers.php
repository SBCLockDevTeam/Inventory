<?php
/**
 * Field Helper Functions
 *
 * Provides functions for managing per-item dynamic fields
 * (item_fields) and their stored values (item_field_values).
 */

require_once __DIR__ . '/item_helpers.php';

// ============================================================
// Field Definition Helpers
// ============================================================

/**
 * Return all field definitions for an item, ordered by sort_order.
 */
function getItemFields(Database $db, string $item_public_code): array
{
    return queryAll($db,
        'SELECT * FROM item_fields WHERE item_public_code = ? ORDER BY sort_order',
        [$item_public_code]
    );
}

/**
 * Add a new field definition to an item.
 * Returns false if a field with the same field_key already exists for the item.
 */
function addItemField(
    Database $db,
    string   $item_public_code,
    string   $field_key,
    string   $label,
    string   $field_type,
    bool     $required     = false,
    int      $sort_order   = 0,
    string   $instructions = ''
): bool {
    $existing = queryOne($db,
        'SELECT id FROM item_fields WHERE item_public_code = ? AND field_key = ?',
        [$item_public_code, $field_key]
    );
    if ($existing) {
        return false;
    }

    execute($db,
        'INSERT INTO item_fields
             (item_public_code, field_key, label, field_type, required, sort_order, instructions)
         VALUES (?, ?, ?, ?, ?, ?, ?)',
        [
            $item_public_code,
            $field_key,
            $label,
            $field_type,
            $required ? 1 : 0,
            $sort_order,
            $instructions,
        ]
    );

    return true;
}

// ============================================================
// Field Value Helpers
// ============================================================

/**
 * Return a map of field_id => value row for all field values of an item.
 * Each row includes field_type from the definition for convenient display.
 */
function getItemFieldValues(Database $db, string $item_public_code): array
{
    $rows = queryAll($db,
        'SELECT v.*, f.field_type, f.field_key, f.label
         FROM item_field_values v
         JOIN  item_fields f ON v.field_id = f.id
         WHERE v.item_public_code = ?',
        [$item_public_code]
    );

    $map = [];
    foreach ($rows as $row) {
        $map[(int)$row['field_id']] = $row;
    }
    return $map;
}

/**
 * Upsert (insert or update) a single field value.
 * The correct typed column is chosen based on the field's field_type.
 */
function saveFieldValue(Database $db, string $item_public_code, int $field_id, $value): bool
{
    $field = queryOne($db,
        'SELECT field_type FROM item_fields WHERE id = ?',
        [$field_id]
    );
    if (!$field) {
        return false;
    }

    $value_text   = null;
    $value_number = null;
    $value_date   = null;
    $value_bool   = null;

    switch ($field['field_type']) {
        case 'text':
        case 'textarea':
            $value_text = $value !== '' ? (string)$value : null;
            break;
        case 'number':
            $value_number = is_numeric($value) ? (float)$value : null;
            break;
        case 'date':
            $value_date = ($value !== '' && $value !== null) ? (string)$value : null;
            break;
        case 'checkbox':
            $value_bool = $value ? 1 : 0;
            break;
        default:
            $value_text = $value !== '' ? (string)$value : null;
    }

    $existing = queryOne($db,
        'SELECT id FROM item_field_values WHERE item_public_code = ? AND field_id = ?',
        [$item_public_code, $field_id]
    );

    if ($existing) {
        execute($db,
            'UPDATE item_field_values
             SET value_text = ?, value_number = ?, value_date = ?,
                 value_bool = ?, updated_at = NOW()
             WHERE item_public_code = ? AND field_id = ?',
            [$value_text, $value_number, $value_date, $value_bool,
             $item_public_code, $field_id]
        );
    } else {
        execute($db,
            'INSERT INTO item_field_values
                 (item_public_code, field_id, value_text, value_number, value_date, value_bool)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$item_public_code, $field_id, $value_text, $value_number, $value_date, $value_bool]
        );
    }

    return true;
}

// ============================================================
// Display Helpers
// ============================================================

/**
 * Return the human-readable display value from a field value row.
 */
function getFieldDisplayValue(array $field_value): string
{
    $type = $field_value['field_type'] ?? '';
    switch ($type) {
        case 'text':
        case 'textarea':
            return $field_value['value_text'] ?? '';
        case 'number':
            if ($field_value['value_number'] === null) {
                return '';
            }
            $n = (float)$field_value['value_number'];
            // Format: strip trailing zeros only when there is a decimal part
            if (floor($n) === $n) {
                return number_format($n, 0, '.', '');
            }
            return rtrim(number_format($n, 4, '.', ''), '0');
        case 'date':
            return $field_value['value_date'] ?? '';
        case 'checkbox':
            return $field_value['value_bool'] ? 'Yes' : 'No';
        default:
            return $field_value['value_text'] ?? '';
    }
}

/**
 * Render an HTML input element for a single field definition.
 * $current_value is the raw stored value (or empty string for new items).
 */
function renderFieldInput(array $field_def, $current_value = ''): void
{
    $field_id   = (int)$field_def['id'];
    $field_name = 'field_' . $field_id;
    $label      = htmlspecialchars($field_def['label']);
    $required   = !empty($field_def['required']) ? 'required' : '';
    $type       = $field_def['field_type'];

    echo '<div class="form-group">';
    echo '<label for="' . $field_name . '">' . $label;
    if (!empty($field_def['required'])) {
        echo ' <span class="required-star">*</span>';
    }
    echo '</label>';

    switch ($type) {
        case 'text':
            echo '<input type="text" id="' . $field_name . '" name="' . $field_name . '"'
               . ' value="' . htmlspecialchars((string)$current_value) . '"'
               . ' class="form-control" maxlength="255" ' . $required . '>';
            break;

        case 'textarea':
            echo '<textarea id="' . $field_name . '" name="' . $field_name . '"'
               . ' class="form-control" rows="3" ' . $required . '>'
               . htmlspecialchars((string)$current_value)
               . '</textarea>';
            break;

        case 'number':
            echo '<input type="number" id="' . $field_name . '" name="' . $field_name . '"'
               . ' value="' . htmlspecialchars((string)$current_value) . '"'
               . ' class="form-control" step="any" ' . $required . '>';
            break;

        case 'date':
            echo '<input type="date" id="' . $field_name . '" name="' . $field_name . '"'
               . ' value="' . htmlspecialchars((string)$current_value) . '"'
               . ' class="form-control" ' . $required . '>';
            break;

        case 'checkbox':
            $checked = $current_value ? 'checked' : '';
            echo '<input type="checkbox" id="' . $field_name . '" name="' . $field_name . '"'
               . ' value="1" class="form-checkbox" ' . $checked . '>';
            break;

        default:
            echo '<input type="text" id="' . $field_name . '" name="' . $field_name . '"'
               . ' value="' . htmlspecialchars((string)$current_value) . '"'
               . ' class="form-control" ' . $required . '>';
    }

    if (!empty($field_def['instructions'])) {
        echo '<small class="field-help">'
           . htmlspecialchars($field_def['instructions'])
           . '</small>';
    }

    echo '</div>';
}
