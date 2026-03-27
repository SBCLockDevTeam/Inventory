/**
 * fields.js
 * Behaviour for the Manage Fields page (admin/items/fields.php).
 *
 * Responsibilities:
 *  - Toggle the "Allow multiple" and "Require printed name" options based
 *    on the selected field type.
 *  - Handle AJAX field deletion with inline confirmation.
 *  - Handle AJAX field edit (inline form toggle or redirect).
 */
document.addEventListener('DOMContentLoaded', function () {

    // ---------------------------------------------------------------
    // Show / hide conditional options depending on field type
    // ---------------------------------------------------------------
    var fieldTypeSelect = document.getElementById('field_type');
    if (fieldTypeSelect) {
        fieldTypeSelect.addEventListener('change', toggleConditionalGroups);
        // Run once on load to restore correct visibility after form errors
        toggleConditionalGroups.call(fieldTypeSelect);
    }

    function toggleConditionalGroups() {
        var type            = this.value;
        var multipleGroup   = document.getElementById('multiple-group');
        var printedNameGrp  = document.getElementById('printed-name-group');

        if (multipleGroup) {
            multipleGroup.style.display =
                ['photo', 'document', 'signature'].includes(type) ? 'block' : 'none';
        }
        if (printedNameGrp) {
            printedNameGrp.style.display = (type === 'signature') ? 'block' : 'none';
        }
    }

    // ---------------------------------------------------------------
    // AJAX field deletion
    // ---------------------------------------------------------------
    document.querySelectorAll('.field-delete-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var fieldId  = this.dataset.fieldId;
            var label    = this.dataset.fieldLabel;
            var itemId   = this.dataset.itemId;
            var endpoint = this.dataset.deleteUrl;
            var row      = this.closest('tr');

            if (!confirm('Delete field "' + label + '"? This will also delete all stored values for this field. This cannot be undone.')) {
                return;
            }

            var formData = new FormData();
            formData.append('field_id', fieldId);
            formData.append('item_id',  itemId);

            fetch(endpoint, { method: 'POST', body: formData })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        if (row) { row.remove(); }
                    } else {
                        alert('Delete failed: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(function (err) {
                    alert('Delete request failed: ' + err);
                });
        });
    });

});
