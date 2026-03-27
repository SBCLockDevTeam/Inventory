<?php
/**
 * LocationHelper
 *
 * Reusable functions for item location tracking and hierarchy navigation.
 * Items form a tree: each item has exactly one parent (location_item_id).
 * Root items are their own parent (location_item_id = public_code).
 */
class LocationHelper {

    /** Maximum ancestor levels to walk; prevents runaway loops on corrupted data */
    const MAX_BREADCRUMB_DEPTH = 50;

    /**
     * Build breadcrumb path from root down to the given item.
     * Returns ordered array of rows (root first, target item last).
     * Returns empty array when item is not found or a cycle is detected.
     *
     * @param string $item_id  10-hex public_code of the target item
     * @return array           Ordered array of ['public_code','name','is_container','location_item_id']
     */
    public static function getLocationBreadcrumb($item_id) {
        $crumbs  = [];
        $visited = [];
        $current = $item_id;

        while (true) {
            // Guard against cycles and runaway depth
            if (isset($visited[$current]) || count($crumbs) > self::MAX_BREADCRUMB_DEPTH) {
                break;
            }
            $visited[$current] = true;

            $row = DatabaseHelper::queryOne(
                "SELECT public_code, name, is_container, location_item_id
                   FROM items
                  WHERE public_code = ?",
                [$current]
            );

            if (!$row) {
                break;
            }

            // Prepend so root ends up at index 0
            array_unshift($crumbs, $row);

            // A root item is its own parent — stop walking up
            if ($row['location_item_id'] === $row['public_code']) {
                break;
            }

            $current = $row['location_item_id'];
        }

        return $crumbs;
    }

    /**
     * Get all container items suitable for use as a parent location.
     * Optionally exclude a set of public_codes (e.g. the item being edited and its descendants).
     *
     * @param array $exclude_codes  public_codes to omit from results
     * @return array                Rows of ['public_code','name','brand_name']
     */
    public static function getAllContainers($exclude_codes = []) {
        if (empty($exclude_codes)) {
            return DatabaseHelper::queryAll(
                "SELECT i.public_code, i.name, b.name AS brand_name
                   FROM items i
                   LEFT JOIN brands b ON i.brand_id = b.id
                  WHERE i.is_container = 1
                  ORDER BY b.name, i.name",
                []
            );
        }

        // Safely build one placeholder per excluded code
        $placeholders = implode(',', array_fill(0, count($exclude_codes), '?'));
        return DatabaseHelper::queryAll(
            "SELECT i.public_code, i.name, b.name AS brand_name
               FROM items i
               LEFT JOIN brands b ON i.brand_id = b.id
              WHERE i.is_container = 1
                AND i.public_code NOT IN ($placeholders)
              ORDER BY b.name, i.name",
            $exclude_codes
        );
    }

    /**
     * Collect all descendant public_codes beneath the given item (breadth-first).
     * Used to prevent an item being moved into one of its own descendants (circular ref).
     *
     * @param string $item_id  Root of the subtree to walk
     * @return array           Flat array of public_codes (the item itself is NOT included)
     */
    public static function getDescendantCodes($item_id) {
        $descendants = [];
        $queue       = [$item_id];

        while (!empty($queue)) {
            $current = array_shift($queue);
            $children = DatabaseHelper::queryAll(
                "SELECT public_code
                   FROM items
                  WHERE location_item_id = ?
                    AND public_code != ?",
                [$current, $current]
            );
            foreach ($children as $child) {
                $descendants[] = $child['public_code'];
                $queue[]        = $child['public_code'];
            }
        }

        return $descendants;
    }

    /**
     * Get the direct children of a container item.
     *
     * @param string $parent_id  public_code of the parent container
     * @return array             Item rows (containers listed first, then non-containers)
     */
    public static function getDirectChildren($parent_id) {
        return DatabaseHelper::queryAll(
            "SELECT i.public_code, i.name, i.is_container, b.name AS brand_name
               FROM items i
               LEFT JOIN brands b ON i.brand_id = b.id
              WHERE i.location_item_id = ?
                AND i.public_code != ?
              ORDER BY i.is_container DESC, i.name",
            [$parent_id, $parent_id]
        );
    }
}
?>
