/**
 * edit_item.js
 * Client-side behaviour specific to admin/items/edit.php.
 *
 * Responsibilities:
 *  - "Add Field" modal: show/hide, conditional option groups, AJAX submit.
 *  - Field sort-order: up/down buttons that call the reorder API then swap
 *    the blocks in the DOM without a full page reload.
 *  - Field delete: inline confirmation before calling field_delete.php.
 */
document.addEventListener('DOMContentLoaded', function () {

    var basePath = document.body.dataset.basePath || '';

    // ---------------------------------------------------------------
    // "Add Field" modal
    // ---------------------------------------------------------------
    var addFieldModal   = document.getElementById('add-field-modal');
    var addFieldOpenBtn = document.getElementById('add-field-btn');
    var addFieldForm    = document.getElementById('add-field-form');

    if (addFieldOpenBtn && addFieldModal) {
        addFieldOpenBtn.addEventListener('click', function () {
            addFieldModal.classList.add('active');
        });
    }

    // Close modal on backdrop click or cancel button
    if (addFieldModal) {
        addFieldModal.addEventListener('click', function (e) {
            if (e.target === addFieldModal) { closeAddFieldModal(); }
        });
        var cancelBtn = addFieldModal.querySelector('.add-field-modal-cancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeAddFieldModal);
        }
    }

    function closeAddFieldModal() {
        if (addFieldModal) {
            addFieldModal.classList.remove('active');
        }
    }

    // Toggle conditional option groups based on selected field type
    var fieldTypeSelect = document.getElementById('af-field_type');
    if (fieldTypeSelect) {
        fieldTypeSelect.addEventListener('change', toggleAddFieldGroups);
        toggleAddFieldGroups.call(fieldTypeSelect);
    }

    function toggleAddFieldGroups() {
        var type           = this.value;
        var multiGroup     = document.getElementById('af-multiple-group');
        var printedGroup   = document.getElementById('af-printed-name-group');

        if (multiGroup) {
            multiGroup.style.display =
                ['photo', 'document', 'signature'].includes(type) ? 'block' : 'none';
        }
        if (printedGroup) {
            printedGroup.style.display = (type === 'signature') ? 'block' : 'none';
        }
    }

    // AJAX submit for add-field form
    if (addFieldForm) {
        addFieldForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(addFieldForm);
            var submitBtn = addFieldForm.querySelector('[type="submit"]');
            if (submitBtn) { submitBtn.disabled = true; }

            fetch(basePath + '/api/add_field.php', { method: 'POST', body: formData })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        // Reload to show the newly added field in full edit form
                        window.location.reload();
                    } else {
                        alert('Add field failed: ' + (data.error || 'Unknown error'));
                        if (submitBtn) { submitBtn.disabled = false; }
                    }
                })
                .catch(function (err) {
                    alert('Request failed: ' + err);
                    if (submitBtn) { submitBtn.disabled = false; }
                });
        });
    }

    // ---------------------------------------------------------------
    // Field sort-order: move up / move down
    // ---------------------------------------------------------------
    var fieldsContainer = document.getElementById('fields-list-container');

    if (fieldsContainer) {
        fieldsContainer.addEventListener('click', function (e) {
            var btn = e.target.closest('.field-order-up, .field-order-down');
            if (!btn) { return; }
            e.preventDefault();

            var fieldId  = btn.dataset.fieldId;
            var itemCode = btn.dataset.itemCode;
            var direction = btn.classList.contains('field-order-up') ? 'up' : 'down';
            var block    = btn.closest('.field-block');

            var formData = new FormData();
            formData.append('field_id',  fieldId);
            formData.append('item_code', itemCode);
            formData.append('direction', direction);

            btn.disabled = true;

            fetch(basePath + '/api/reorder_field.php', { method: 'POST', body: formData })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        // Swap the field-block elements in the DOM for instant feedback
                        if (direction === 'up') {
                            var prev = getPreviousFieldBlock(block);
                            if (prev) { fieldsContainer.insertBefore(block, prev); }
                        } else {
                            var next = getNextFieldBlock(block);
                            if (next) { fieldsContainer.insertBefore(next, block); }
                        }
                    } else {
                        alert('Reorder failed: ' + (data.error || 'Unknown error'));
                    }
                    btn.disabled = false;
                })
                .catch(function (err) {
                    alert('Request failed: ' + err);
                    btn.disabled = false;
                });
        });
    }

    function getPreviousFieldBlock(block) {
        var prev = block.previousElementSibling;
        while (prev && !prev.classList.contains('field-block')) {
            prev = prev.previousElementSibling;
        }
        return prev;
    }

    function getNextFieldBlock(block) {
        var next = block.nextElementSibling;
        while (next && !next.classList.contains('field-block')) {
            next = next.nextElementSibling;
        }
        return next;
    }

    // ---------------------------------------------------------------
    // Field delete (inline, from edit page)
    // ---------------------------------------------------------------
    if (fieldsContainer) {
        fieldsContainer.addEventListener('click', function (e) {
            var btn = e.target.closest('.field-delete-inline');
            if (!btn) { return; }
            e.preventDefault();

            var fieldId   = btn.dataset.fieldId;
            var fieldLabel = btn.dataset.fieldLabel;
            var itemCode  = btn.dataset.itemCode;
            var block     = btn.closest('.field-block');

            if (!confirm('Delete field "' + fieldLabel + '"? All stored values for this field will also be deleted. This cannot be undone.')) {
                return;
            }

            var formData = new FormData();
            formData.append('field_id', fieldId);
            formData.append('item_id',  itemCode);

            btn.disabled = true;

            fetch(basePath + '/admin/items/field_delete.php', { method: 'POST', body: formData })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        if (block) { block.remove(); }
                    } else {
                        alert('Delete failed: ' + (data.error || 'Unknown error'));
                        btn.disabled = false;
                    }
                })
                .catch(function (err) {
                    alert('Request failed: ' + err);
                    btn.disabled = false;
                });
        });
    }

});
