<?php
/**
 * Item Helper Functions
 * 
 * Provides reusable functions for item operations including validation,
 * circular reference detection, hierarchy management, and item operations.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/logger.php';

/**
 * Check if moving an item would create a circular reference
 * 
 * @param PDO $db Database connection
 * @param string $item_id Item being moved
 * @param string $new_parent_id Proposed new parent
 * @return bool True if circular reference would be created
 */
function wouldCreateCircularReference($db, $item_id, $new_parent_id) {
    // If new parent is the item itself, that's circular
    if ($item_id === $new_parent_id) {
        return true;
    }
    
    // Check if the new parent is a descendant of the item being moved
    $current_id = $new_parent_id;
    $visited = [];
    
    while ($current_id !== null && $current_id !== '') {
        // Prevent infinite loops
        if (in_array($current_id, $visited)) {
            return true;
        }
        $visited[] = $current_id;
        
        // If we've reached the item being moved, we have a circular reference
        if ($current_id === $item_id) {
            return true;
        }
        
        // Get the parent of current item
        $parent = queryOne($db, "SELECT location_item_id FROM items WHERE item_id = ?", [$current_id]);
        
        if (!$parent) {
            break;
        }
        
        // If parent points to itself, it's a root item
        if ($parent['location_item_id'] === $current_id) {
            break;
        }
        
        $current_id = $parent['location_item_id'];
    }
    
    return false;
}

/**
 * Get all descendants of an item
 * 
 * @param PDO $db Database connection
 * @param string $item_id Parent item ID
 * @return array Array of descendant item IDs
 */
function getDescendants($db, $item_id) {
    $descendants = [];
    $to_process = [$item_id];
    
    while (!empty($to_process)) {
        $current = array_shift($to_process);
        
        // Get direct children
        $children = queryAll($db, 
            "SELECT item_id FROM items WHERE location_item_id = ? AND item_id != location_item_id",
            [$current]
        );
        
        foreach ($children as $child) {
            $child_id = $child['item_id'];
            if (!in_array($child_id, $descendants)) {
                $descendants[] = $child_id;
                $to_process[] = $child_id;
            }
        }
    }
    
    return $descendants;
}

/**
 * Check if an item is a container
 * 
 * @param PDO $db Database connection
 * @param string $item_id Item ID
 * @return bool True if item is a container
 */
function isContainer($db, $item_id) {
    $item = queryOne($db, "SELECT is_container FROM items WHERE item_id = ?", [$item_id]);
    return $item && $item['is_container'] == 1;
}

/**
 * Get children of a container item
 * 
 * @param PDO $db Database connection
 * @param string $item_id Parent item ID
 * @return array Array of child items
 */
function getChildren($db, $item_id) {
    return queryAll($db,
        "SELECT * FROM items WHERE location_item_id = ? AND item_id != location_item_id ORDER BY item_name",
        [$item_id]
    );
}

/**
 * Check if an item is a root item
 * 
 * @param PDO $db Database connection
 * @param string $item_id Item ID
 * @return bool True if item is a root item (its own parent)
 */
function isRootItem($db, $item_id) {
    $item = queryOne($db, "SELECT location_item_id FROM items WHERE item_id = ?", [$item_id]);
    return $item && $item['location_item_id'] === $item_id;
}

/**
 * Move item's children to a new parent
 * 
 * @param PDO $db Database connection
 * @param string $item_id Item whose children to move
 * @param string $new_parent_id New parent for the children
 * @return int Number of children moved
 */
function moveChildrenToParent($db, $item_id, $new_parent_id) {
    $affected = execute($db,
        "UPDATE items SET location_item_id = ? WHERE location_item_id = ? AND item_id != ?",
        [$new_parent_id, $item_id, $item_id]
    );
    
    return $affected;
}

/**
 * Clone an item's custom fields structure (without data)
 * 
 * @param PDO $db Database connection
 * @param string $source_item_id Source item ID
 * @param string $target_item_id Target item ID
 * @return int Number of fields cloned
 */
function cloneItemFieldsStructure($db, $source_item_id, $target_item_id) {
    // Get all custom fields from source item
    $fields = queryAll($db,
        "SELECT field_name, field_type, field_config FROM item_fields WHERE item_id = ? ORDER BY field_order",
        [$source_item_id]
    );
    
    $count = 0;
    foreach ($fields as $index => $field) {
        execute($db,
            "INSERT INTO item_fields (item_id, field_name, field_type, field_config, field_order) VALUES (?, ?, ?, ?, ?)",
            [$target_item_id, $field['field_name'], $field['field_type'], $field['field_config'], $index]
        );
        $count++;
    }
    
    return $count;
}

/**
 * Clone an item's custom fields with data
 * 
 * @param PDO $db Database connection
 * @param string $source_item_id Source item ID
 * @param string $target_item_id Target item ID
 * @return int Number of fields cloned
 */
function cloneItemFieldsWithData($db, $source_item_id, $target_item_id) {
    // Get all custom fields from source item
    $fields = queryAll($db,
        "SELECT field_name, field_type, field_config FROM item_fields WHERE item_id = ? ORDER BY field_order",
        [$source_item_id]
    );
    
    $count = 0;
    foreach ($fields as $index => $field) {
        // Insert field structure
        execute($db,
            "INSERT INTO item_fields (item_id, field_name, field_type, field_config, field_order) VALUES (?, ?, ?, ?, ?)",
            [$target_item_id, $field['field_name'], $field['field_type'], $field['field_config'], $index]
        );
        
        // Copy field data
        $field_data = queryOne($db,
            "SELECT field_value FROM item_field_data WHERE item_id = ? AND field_name = ?",
            [$source_item_id, $field['field_name']]
        );
        
        if ($field_data) {
            execute($db,
                "INSERT INTO item_field_data (item_id, field_name, field_value) VALUES (?, ?, ?)",
                [$target_item_id, $field['field_name'], $field_data['field_value']]
            );
        }
        
        $count++;
    }
    
    return $count;
}

/**
 * Generate a new unique 10-character hex item ID
 * 
 * @param PDO $db Database connection
 * @return string New unique item ID
 */
function generateItemId($db) {
    do {
        $item_id = bin2hex(random_bytes(5)); // 10 hex characters
        $exists = queryOne($db, "SELECT item_id FROM items WHERE item_id = ?", [$item_id]);
    } while ($exists);
    
    return $item_id;
}

/**
 * Delete an item and handle its children
 * 
 * @param PDO $db Database connection
 * @param string $item_id Item to delete
 * @return array ['success' => bool, 'message' => string, 'children_moved' => int]
 */
function deleteItem($db, $item_id) {
    try {
        beginTransaction($db);
        
        // Get item details
        $item = queryOne($db, "SELECT * FROM items WHERE item_id = ?", [$item_id]);
        
        if (!$item) {
            rollback($db);
            return ['success' => false, 'message' => 'Item not found', 'children_moved' => 0];
        }
        
        // Check if it's a root item
        if (isRootItem($db, $item_id)) {
            rollback($db);
            return ['success' => false, 'message' => 'Cannot delete root items', 'children_moved' => 0];
        }
        
        $parent_id = $item['location_item_id'];
        
        // Move children to parent
        $children_moved = moveChildrenToParent($db, $item_id, $parent_id);
        
        // Delete custom field data
        execute($db, "DELETE FROM item_field_data WHERE item_id = ?", [$item_id]);
        
        // Delete custom field definitions
        execute($db, "DELETE FROM item_fields WHERE item_id = ?", [$item_id]);
        
        // Delete the item
        execute($db, "DELETE FROM items WHERE item_id = ?", [$item_id]);
        
        // Log the deletion
        logActivity("Item deleted: {$item['item_name']} (ID: {$item_id}), {$children_moved} children moved to parent");
        
        commit($db);
        
        return [
            'success' => true,
            'message' => 'Item deleted successfully',
            'children_moved' => $children_moved
        ];
        
    } catch (Exception $e) {
        rollback($db);
        return ['success' => false, 'message' => 'Error deleting item: ' . $e->getMessage(), 'children_moved' => 0];
    }
}

/**
 * Get item with all its custom fields and data
 * 
 * @param PDO $db Database connection
 * @param string $item_id Item ID
 * @return array|null Item data with fields or null if not found
 */
function getItemWithFields($db, $item_id) {
    $item = queryOne($db, "SELECT * FROM items WHERE item_id = ?", [$item_id]);
    
    if (!$item) {
        return null;
    }
    
    // Get custom fields
    $fields = queryAll($db,
        "SELECT f.*, d.field_value 
         FROM item_fields f 
         LEFT JOIN item_field_data d ON f.item_id = d.item_id AND f.field_name = d.field_name 
         WHERE f.item_id = ? 
         ORDER BY f.field_order",
        [$item_id]
    );
    
    $item['custom_fields'] = $fields;
    
    return $item;
}

/**
 * Validate item data
 * 
 * @param array $data Item data
 * @return array ['valid' => bool, 'errors' => array]
 */
function validateItemData($data) {
    $errors = [];
    
    // Item Name is required
    if (empty($data['item_name'])) {
        $errors[] = 'Item Name is required';
    }
    
    // Item Description is required
    if (empty($data['item_description'])) {
        $errors[] = 'Item Description is required';
    }
    
    // Parent item is required
    if (empty($data['location_item_id'])) {
        $errors[] = 'Parent location is required';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}