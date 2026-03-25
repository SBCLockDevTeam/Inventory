<?php
/**
 * Item Helper Functions
 *
 * Provides reusable functions for item operations including standalone DB
 * helpers, circular-reference detection, hierarchy management, and CRUD.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/logger.php';

// ============================================================
// Standalone Database Helper Functions
// ============================================================

/**
 * Execute a SELECT query and return the first row, or null.
 */
function queryOne(Database $db, string $sql, array $params = []): ?array
{
    $db->query($sql);
    foreach ($params as $i => $val) {
        $db->bind($i + 1, $val);
    }
    $row = $db->queryOne();
    return $row ?: null;
}

/**
 * Execute a SELECT query and return all rows.
 */
function queryAll(Database $db, string $sql, array $params = []): array
{
    $db->query($sql);
    foreach ($params as $i => $val) {
        $db->bind($i + 1, $val);
    }
    return $db->queryAll() ?: [];
}

/**
 * Execute a DML statement (INSERT / UPDATE / DELETE) and return affected rows.
 */
function execute(Database $db, string $sql, array $params = []): int
{
    $db->query($sql);
    foreach ($params as $i => $val) {
        $db->bind($i + 1, $val);
    }
    $db->execute();
    return $db->queryCount();
}

function beginTransaction(Database $db): bool
{
    return $db->beginTransaction();
}

function commit(Database $db): bool
{
    return $db->commit();
}

function rollback(Database $db): bool
{
    return $db->rollback();
}

/**
 * Append a line to the activity log.
 */
function logActivity(string $message): void
{
    $logFile = __DIR__ . '/../logs/activity.log';
    if (!is_dir(dirname($logFile))) {
        @mkdir(dirname($logFile), 0775, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
}

// ============================================================
// Public Code Generation
// ============================================================

/**
 * Generate a new unique 10-character uppercase hex public code.
 */
function generatePublicCode(Database $db): string
{
    $max_attempts = 100;
    for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
        $code   = strtoupper(bin2hex(random_bytes(5)));
        $exists = queryOne($db, 'SELECT public_code FROM items WHERE public_code = ?', [$code]);
        if (!$exists) {
            return $code;
        }
    }
    throw new RuntimeException('Unable to generate a unique public code after ' . $max_attempts . ' attempts');
}

// ============================================================
// Item CRUD
// ============================================================

/**
 * Create a new item and return its generated public_code.
 *
 * @param array $data  Keys: name (required), description, brand_id (required),
 *                     location_item_id, is_container, primary_image
 */
function createItem(Database $db, array $data): string
{
    $public_code = generatePublicCode($db);

    // If no parent supplied, root-item: own parent
    $location = $data['location_item_id'] ?? $public_code;

    execute($db,
        'INSERT INTO items
             (public_code, brand_id, name, description, is_container, location_item_id, primary_image, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
        [
            $public_code,
            (int)$data['brand_id'],
            $data['name'],
            $data['description'] ?? null,
            !empty($data['is_container']) ? 1 : 0,
            $location,
            $data['primary_image'] ?? null,
        ]
    );

    return $public_code;
}

/**
 * Update an existing item. Only keys present in $data are updated.
 */
function updateItem(Database $db, string $public_code, array $data): bool
{
    $fields = [];
    $params = [];

    if (isset($data['name'])) {
        $fields[] = 'name = ?';
        $params[] = $data['name'];
    }
    if (array_key_exists('description', $data)) {
        $fields[] = 'description = ?';
        $params[] = $data['description'];
    }
    if (isset($data['brand_id'])) {
        $fields[] = 'brand_id = ?';
        $params[] = (int)$data['brand_id'];
    }
    if (isset($data['is_container'])) {
        $fields[] = 'is_container = ?';
        $params[] = !empty($data['is_container']) ? 1 : 0;
    }
    if (isset($data['location_item_id'])) {
        $fields[] = 'location_item_id = ?';
        $params[] = $data['location_item_id'];
    }
    if (array_key_exists('primary_image', $data)) {
        $fields[] = 'primary_image = ?';
        $params[] = $data['primary_image'];
    }

    if (empty($fields)) {
        return false;
    }

    $fields[] = 'updated_at = NOW()';
    $params[] = $public_code;

    $sql = 'UPDATE items SET ' . implode(', ', $fields) . ' WHERE public_code = ?';
    return execute($db, $sql, $params) > 0;
}

/**
 * Fetch a single item (with brand name) by its public_code.
 */
function getItemByPublicCode(Database $db, string $public_code): ?array
{
    return queryOne($db,
        'SELECT i.*, b.name AS brand_name
         FROM items i
         LEFT JOIN brands b ON i.brand_id = b.id
         WHERE i.public_code = ?',
        [$public_code]
    );
}

/**
 * Fetch an item together with all its custom fields and current values.
 * Fields are stored in $item['fields'].
 */
function getItemWithFields(Database $db, string $public_code): ?array
{
    $item = getItemByPublicCode($db, $public_code);
    if (!$item) {
        return null;
    }

    $fields = queryAll($db,
        'SELECT f.*, v.value_text, v.value_number, v.value_date, v.value_bool
         FROM item_fields f
         LEFT JOIN item_field_values v
               ON f.id = v.field_id AND v.item_public_code = ?
         WHERE f.item_public_code = ?
         ORDER BY f.sort_order',
        [$public_code, $public_code]
    );

    $item['fields'] = $fields;
    return $item;
}

/**
 * Return all container items for use in parent-selection dropdowns.
 */
function getContainers(Database $db): array
{
    return queryAll($db,
        'SELECT public_code, name FROM items WHERE is_container = 1 ORDER BY name'
    );
}

/**
 * Return all brands for use in brand-selection dropdowns.
 */
function getBrands(Database $db): array
{
    return queryAll($db, 'SELECT id, name FROM brands ORDER BY name');
}

/**
 * Return direct children of a container.
 */
function getChildren(Database $db, string $public_code): array
{
    return queryAll($db,
        'SELECT * FROM items
         WHERE location_item_id = ? AND public_code != ?
         ORDER BY name',
        [$public_code, $public_code]
    );
}

/**
 * Return the breadcrumb trail from root down to (and including) $public_code.
 * Each element is an array with keys: public_code, name, location_item_id.
 */
function getItemBreadcrumb(Database $db, string $public_code): array
{
    $crumbs  = [];
    $visited = [];
    $current = $public_code;

    while ($current !== null && $current !== '') {
        if (in_array($current, $visited, true)) {
            break;
        }
        $visited[] = $current;

        $item = queryOne($db,
            'SELECT public_code, name, location_item_id FROM items WHERE public_code = ?',
            [$current]
        );
        if (!$item) {
            break;
        }

        array_unshift($crumbs, $item);

        if ($item['location_item_id'] === $current) {
            break; // reached root
        }
        $current = $item['location_item_id'];
    }

    return $crumbs;
}

/**
 * Check whether an item is a container.
 */
function isContainer(Database $db, string $public_code): bool
{
    $item = queryOne($db,
        'SELECT is_container FROM items WHERE public_code = ?',
        [$public_code]
    );
    return $item && (int)$item['is_container'] === 1;
}

/**
 * Check whether an item is a root item (its own parent).
 */
function isRootItem(Database $db, string $public_code): bool
{
    $item = queryOne($db,
        'SELECT location_item_id FROM items WHERE public_code = ?',
        [$public_code]
    );
    return $item && $item['location_item_id'] === $public_code;
}

// ============================================================
// Hierarchy / Movement Helpers
// ============================================================

/**
 * Return true if moving $item_code under $new_parent_code would
 * create a circular reference.
 */
function wouldCreateCircularReference(Database $db, string $item_code, string $new_parent_code): bool
{
    if ($item_code === $new_parent_code) {
        return true;
    }

    $current = $new_parent_code;
    $visited = [];

    while ($current !== null && $current !== '') {
        if (in_array($current, $visited, true)) {
            return true;
        }
        $visited[] = $current;

        if ($current === $item_code) {
            return true;
        }

        $parent = queryOne($db,
            'SELECT location_item_id FROM items WHERE public_code = ?',
            [$current]
        );
        if (!$parent) {
            break;
        }
        if ($parent['location_item_id'] === $current) {
            break; // root
        }
        $current = $parent['location_item_id'];
    }

    return false;
}

/**
 * Return all descendant public_codes of an item (not including itself).
 */
function getDescendants(Database $db, string $public_code): array
{
    $descendants = [];
    $to_process  = [$public_code];

    while (!empty($to_process)) {
        $current  = array_shift($to_process);
        $children = queryAll($db,
            'SELECT public_code FROM items WHERE location_item_id = ? AND public_code != ?',
            [$current, $current]
        );
        foreach ($children as $child) {
            $code = $child['public_code'];
            if (!in_array($code, $descendants, true)) {
                $descendants[] = $code;
                $to_process[]  = $code;
            }
        }
    }

    return $descendants;
}

/**
 * Reparent all direct children of $item_code to $new_parent_code.
 * Returns the number of rows updated.
 */
function moveChildrenToParent(Database $db, string $item_code, string $new_parent_code): int
{
    return execute($db,
        'UPDATE items SET location_item_id = ?
         WHERE location_item_id = ? AND public_code != ?',
        [$new_parent_code, $item_code, $item_code]
    );
}

// ============================================================
// Clone Helper
// ============================================================

/**
 * Copy all field definitions AND their current values from
 * $source_code to $target_code.  Returns the number of fields copied.
 */
function cloneItemFields(Database $db, string $source_code, string $target_code): int
{
    $fields = queryAll($db,
        'SELECT f.*,
                v.value_text, v.value_number, v.value_date, v.value_bool
         FROM item_fields f
         LEFT JOIN item_field_values v
               ON f.id = v.field_id AND v.item_public_code = ?
         WHERE f.item_public_code = ?
         ORDER BY f.sort_order',
        [$source_code, $source_code]
    );

    $count = 0;
    foreach ($fields as $field) {
        execute($db,
            'INSERT INTO item_fields
                 (item_public_code, field_key, label, field_type,
                  required, sort_order, allow_multiple, instructions)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $target_code,
                $field['field_key'],
                $field['label'],
                $field['field_type'],
                (int)$field['required'],
                (int)$field['sort_order'],
                (int)$field['allow_multiple'],
                $field['instructions'],
            ]
        );

        // Look up the newly inserted field ID by (item_public_code, field_key)
        $new_field = queryOne($db,
            'SELECT id FROM item_fields WHERE item_public_code = ? AND field_key = ?',
            [$target_code, $field['field_key']]
        );

        if ($new_field &&
            ($field['value_text']   !== null ||
             $field['value_number'] !== null ||
             $field['value_date']   !== null ||
             $field['value_bool']   !== null)) {
            execute($db,
                'INSERT INTO item_field_values
                     (item_public_code, field_id, value_text, value_number, value_date, value_bool)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $target_code,
                    (int)$new_field['id'],
                    $field['value_text'],
                    $field['value_number'],
                    $field['value_date'],
                    $field['value_bool'],
                ]
            );
        }

        $count++;
    }

    return $count;
}

// ============================================================
// Delete
// ============================================================

/**
 * Delete an item and reassign its children to its parent.
 *
 * @return array{success: bool, message: string, children_moved: int, image: string|null}
 */
function deleteItem(Database $db, string $public_code): array
{
    try {
        beginTransaction($db);

        $item = queryOne($db, 'SELECT * FROM items WHERE public_code = ?', [$public_code]);
        if (!$item) {
            rollback($db);
            return ['success' => false, 'message' => 'Item not found', 'children_moved' => 0, 'image' => null];
        }

        if (isRootItem($db, $public_code)) {
            rollback($db);
            return ['success' => false, 'message' => 'Cannot delete root items', 'children_moved' => 0, 'image' => null];
        }

        $parent_code    = $item['location_item_id'];
        $children_moved = moveChildrenToParent($db, $public_code, $parent_code);

        // Cascade deletes via FK ON DELETE CASCADE, but be explicit just in case
        execute($db, 'DELETE FROM item_field_values WHERE item_public_code = ?', [$public_code]);
        execute($db, 'DELETE FROM item_fields       WHERE item_public_code = ?', [$public_code]);
        execute($db, 'DELETE FROM items             WHERE public_code = ?',      [$public_code]);

        logActivity("Item deleted: {$item['name']} (Code: {$public_code}), {$children_moved} children moved to parent");

        commit($db);

        return [
            'success'       => true,
            'message'       => 'Item deleted successfully',
            'children_moved'=> $children_moved,
            'image'         => $item['primary_image'],
        ];

    } catch (Exception $e) {
        rollback($db);
        return ['success' => false, 'message' => 'Error deleting item: ' . $e->getMessage(), 'children_moved' => 0, 'image' => null];
    }
}

// ============================================================
// Validation
// ============================================================

/**
 * Validate core item data.
 *
 * @return array{valid: bool, errors: string[]}
 */
function validateItemData(array $data): array
{
    $errors = [];

    if (empty($data['name'])) {
        $errors[] = 'Item name is required';
    }

    if (empty($data['brand_id'])) {
        $errors[] = 'Brand is required';
    }

    if (empty($data['location_item_id'])) {
        $errors[] = 'Parent location is required';
    }

    return [
        'valid'  => empty($errors),
        'errors' => $errors,
    ];
}