<?php
/**
 * TreeHelper
 *
 * Builds and renders a collapsible tree view of items.
 *
 * Items form a hierarchy where each item points to a parent via
 * location_item_id.  Root items are self-referencing
 * (location_item_id = public_code).
 */
class TreeHelper {

    /**
     * Fetch ALL items in a single query and organise them into a nested
     * tree structure.
     *
     * Returns an ordered array of root-level nodes, each of which may
     * contain a 'children' key recursively.
     *
     * @return array  Tree-structured array of item nodes
     */
    public static function buildTree() {
        $rows = DatabaseHelper::queryAll(
            "SELECT public_code, name, is_container, location_item_id
               FROM items
              ORDER BY is_container DESC, name",
            []
        );

        // Index rows by public_code for O(1) child attachment
        $by_id = [];
        foreach ($rows as $row) {
            $row['children'] = [];
            $by_id[$row['public_code']] = $row;
        }

        // Attach each non-root item to its parent's children array
        $roots = [];
        foreach ($by_id as $code => &$node) {
            $is_root = ($node['location_item_id'] === $node['public_code']);
            if ($is_root) {
                $roots[] = &$node;
            } elseif (isset($by_id[$node['location_item_id']])) {
                $by_id[$node['location_item_id']]['children'][] = &$node;
            } else {
                // Orphaned item – surface at root level so it is not lost
                $roots[] = &$node;
            }
        }
        unset($node);

        return $roots;
    }

    /**
     * Render the tree as an HTML <ul> element.
     *
     * @param array  $nodes      Array of tree nodes (from buildTree())
     * @param string $view_base  URL prefix for item view links
     *                           e.g. BASE_PATH . '/items/view.php?id='
     * @param int    $depth      Current recursion depth (0 = roots)
     * @return string            HTML string
     */
    public static function renderTree(array $nodes, $view_base, $depth = 0) {
        if (empty($nodes)) {
            if ($depth === 0) {
                return '<ul class="item-tree" role="tree">'
                     . '<li class="tree-empty">No items found.</li></ul>';
            }
            return '';
        }

        // Outer <ul>: root = role="tree", nested = role="group"
        $role = ($depth === 0) ? 'role="tree"' : 'role="group"';
        $html = '<ul class="item-tree" ' . $role . '>' . "\n";

        foreach ($nodes as $node) {
            $has_children = !empty($node['children']);
            $is_container = (bool)$node['is_container'];
            $name_esc     = htmlspecialchars($node['name'], ENT_QUOTES, 'UTF-8');
            $code_esc     = htmlspecialchars($node['public_code'], ENT_QUOTES, 'UTF-8');
            $href         = $view_base . $code_esc;

            $html .= '<li class="tree-node" role="treeitem">' . "\n";
            $html .= '<div class="tree-row">' . "\n";

            if ($is_container && $has_children) {
                $html .= '<button class="tree-toggle" aria-expanded="false"'
                       . ' aria-label="Expand ' . $name_esc . '">&#9658;</button>' . "\n";
            } else {
                $html .= '<span class="tree-leaf-spacer" aria-hidden="true"></span>' . "\n";
            }

            $icon  = $is_container ? '📦' : '🔧';
            $html .= '<span class="tree-icon" aria-hidden="true">' . $icon . '</span>' . "\n";
            $html .= '<a class="tree-label" href="' . $href . '">' . $name_esc . '</a>' . "\n";

            if ($is_container) {
                $html .= '<span class="tree-container-badge">Container</span>' . "\n";
            }

            $html .= '</div>' . "\n"; // .tree-row

            if ($is_container && $has_children) {
                $html .= self::renderTree($node['children'], $view_base, $depth + 1);
            }

            $html .= '</li>' . "\n"; // .tree-node
        }

        $html .= '</ul>' . "\n";
        return $html;
    }
}
?>
