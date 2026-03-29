/**
 * item_tree.js
 *
 * Handles expand/collapse for the hierarchical item tree view,
 * and client-side filtering to highlight matching nodes.
 *
 * Expand/collapse state is stored in localStorage so it survives
 * navigation across tabs and browser restarts.
 */
document.addEventListener('DOMContentLoaded', function () {

    var STORAGE_KEY = 'itemTreeExpanded';

    // ---------------------------------------------------------------
    // localStorage helpers – store a map of { itemId: true } for
    // every node that is currently open.
    // ---------------------------------------------------------------
    function loadExpanded() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (e) {
            return {};
        }
    }

    function saveExpanded(map) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(map));
        } catch (e) { /* localStorage unavailable – silent fail */ }
    }

    function setNodeExpanded(itemId, open) {
        var map = loadExpanded();
        if (open) {
            map[itemId] = true;
        } else {
            delete map[itemId];
        }
        saveExpanded(map);
    }

    // ---------------------------------------------------------------
    // Restore persisted expand/collapse state on page load
    // ---------------------------------------------------------------
    function applyExpanded(expandedMap) {
        document.querySelectorAll('.tree-node[data-item-id]').forEach(function (node) {
            var subtree = node.querySelector(':scope > .item-tree');
            var toggle  = node.querySelector(':scope > .tree-row .tree-toggle');
            if (!subtree || !toggle) { return; }
            var open = !!expandedMap[node.getAttribute('data-item-id')];
            subtree.classList.toggle('tree-open', open);
            toggle.classList.toggle('open', open);
            toggle.setAttribute('aria-expanded', String(open));
        });
    }

    applyExpanded(loadExpanded());

    // ---------------------------------------------------------------
    // Expand / Collapse individual nodes
    // ---------------------------------------------------------------
    document.querySelectorAll('.tree-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var treeNode = this.closest('.tree-node');
            var subtree  = treeNode ? treeNode.querySelector(':scope > .item-tree') : null;
            if (!subtree) { return; }
            var open = subtree.classList.toggle('tree-open');
            this.classList.toggle('open', open);
            this.setAttribute('aria-expanded', String(open));
            // Persist the change for the current session
            var itemId = treeNode.getAttribute('data-item-id');
            if (itemId) { setNodeExpanded(itemId, open); }
        });
    });

    // ---------------------------------------------------------------
    // "Expand All" / "Collapse All" buttons
    // ---------------------------------------------------------------
    var expandAllBtn   = document.getElementById('tree-expand-all');
    var collapseAllBtn = document.getElementById('tree-collapse-all');

    if (expandAllBtn) {
        expandAllBtn.addEventListener('click', function () {
            var map = {};
            document.querySelectorAll('.item-tree .item-tree').forEach(function (ul) {
                ul.classList.add('tree-open');
            });
            document.querySelectorAll('.tree-node[data-item-id]').forEach(function (node) {
                var subtree = node.querySelector(':scope > .item-tree');
                var toggle  = node.querySelector(':scope > .tree-row .tree-toggle');
                if (!subtree || !toggle) { return; }
                toggle.classList.add('open');
                toggle.setAttribute('aria-expanded', 'true');
                map[node.getAttribute('data-item-id')] = true;
            });
            saveExpanded(map);
        });
    }

    if (collapseAllBtn) {
        collapseAllBtn.addEventListener('click', function () {
            document.querySelectorAll('.item-tree .item-tree').forEach(function (ul) {
                ul.classList.remove('tree-open');
            });
            document.querySelectorAll('.tree-toggle').forEach(function (btn) {
                btn.classList.remove('open');
                btn.setAttribute('aria-expanded', 'false');
            });
            saveExpanded({});
        });
    }

    // ---------------------------------------------------------------
    // Client-side live filter
    // Highlights matching nodes and expands their ancestors.
    // When the filter is cleared the persisted state is re-applied.
    // ---------------------------------------------------------------
    var filterInput = document.getElementById('tree-filter-input');
    if (!filterInput) { return; }

    filterInput.addEventListener('input', function () {
        var query    = this.value.trim().toLowerCase();
        var allNodes = document.querySelectorAll('.tree-node');

        if (!query) {
            // Remove highlights, then restore the saved expand/collapse state
            allNodes.forEach(function (node) {
                node.style.display = '';
                node.classList.remove('tree-match');
                var label = node.querySelector(':scope > .tree-row .tree-label');
                if (label) { label.classList.remove('tree-match'); }
            });
            // Collapse everything first, then re-apply persisted state
            document.querySelectorAll('.item-tree .item-tree').forEach(function (ul) {
                ul.classList.remove('tree-open');
            });
            document.querySelectorAll('.tree-toggle').forEach(function (btn) {
                btn.classList.remove('open');
                btn.setAttribute('aria-expanded', 'false');
            });
            applyExpanded(loadExpanded());
            return;
        }

        // First pass: mark matching nodes
        allNodes.forEach(function (node) {
            var label = node.querySelector(':scope > .tree-row .tree-label');
            if (!label) { return; }
            var matches = label.textContent.toLowerCase().indexOf(query) >= 0;
            node.classList.toggle('tree-match', matches);
            label.classList.toggle('tree-match', matches);
            // Hide non-matching leaf nodes; will be re-shown if a child matches
            node.style.display = matches ? '' : 'none';
        });

        // Second pass: ensure ancestors of matched nodes are visible and expanded
        document.querySelectorAll('.tree-node.tree-match').forEach(function (matchedNode) {
            // Walk up the DOM to show all ancestor nodes and open their subtrees
            var parent = matchedNode.parentElement;
            while (parent) {
                if (parent.classList.contains('item-tree')) {
                    parent.classList.add('tree-open');
                    // Find and mark toggle as open
                    var ownerNode = parent.closest('.tree-node');
                    if (ownerNode) {
                        ownerNode.style.display = '';
                        var toggle = ownerNode.querySelector(':scope > .tree-row .tree-toggle');
                        if (toggle) {
                            toggle.classList.add('open');
                            toggle.setAttribute('aria-expanded', 'true');
                        }
                    }
                }
                parent = parent.parentElement;
            }
        });
    });

});