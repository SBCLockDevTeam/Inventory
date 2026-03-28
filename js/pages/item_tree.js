/**
 * item_tree.js
 *
 * Handles expand/collapse for the hierarchical item tree view,
 * and client-side filtering to highlight matching nodes.
 */
document.addEventListener('DOMContentLoaded', function () {

    // ---------------------------------------------------------------
    // Expand / Collapse individual nodes
    // ---------------------------------------------------------------
    document.querySelectorAll('.tree-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var subtree = this.closest('.tree-node').querySelector(':scope > .item-tree');
            if (!subtree) { return; }
            var open = subtree.classList.toggle('tree-open');
            this.classList.toggle('open', open);
            this.setAttribute('aria-expanded', String(open));
        });
    });

    // ---------------------------------------------------------------
    // "Expand All" / "Collapse All" buttons
    // ---------------------------------------------------------------
    var expandAllBtn   = document.getElementById('tree-expand-all');
    var collapseAllBtn = document.getElementById('tree-collapse-all');

    if (expandAllBtn) {
        expandAllBtn.addEventListener('click', function () {
            document.querySelectorAll('.item-tree .item-tree').forEach(function (ul) {
                ul.classList.add('tree-open');
            });
            document.querySelectorAll('.tree-toggle').forEach(function (btn) {
                btn.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');
            });
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
        });
    }

    // ---------------------------------------------------------------
    // Client-side live filter
    // Highlights matching nodes and expands their ancestors.
    // ---------------------------------------------------------------
    var filterInput = document.getElementById('tree-filter-input');
    if (!filterInput) { return; }

    filterInput.addEventListener('input', function () {
        var query = this.value.trim().toLowerCase();
        var allNodes = document.querySelectorAll('.tree-node');

        if (!query) {
            // Reset: remove highlights and collapse back to default
            allNodes.forEach(function (node) {
                node.style.display = '';
                node.classList.remove('tree-match');
                var label = node.querySelector(':scope > .tree-row .tree-label');
                if (label) { label.classList.remove('tree-match'); }
            });
            document.querySelectorAll('.item-tree .item-tree').forEach(function (ul) {
                ul.classList.remove('tree-open');
            });
            document.querySelectorAll('.tree-toggle').forEach(function (btn) {
                btn.classList.remove('open');
                btn.setAttribute('aria-expanded', 'false');
            });
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