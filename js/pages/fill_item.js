/**
 * fill_item.js
 * Client-side behaviour for admin/items/values.php.
 *
 * Initialises photo upload, document upload, and signature widgets
 * for every dynamic field rendered on the page.
 * Configuration data is injected by the PHP page via data- attributes
 * on each field's container element.
 */
document.addEventListener('DOMContentLoaded', function () {

    var basePath = (typeof BASE_PATH !== 'undefined') ? BASE_PATH : (document.body.dataset.basePath || '');

    // ---------------------------------------------------------------
    // Photo fields
    // ---------------------------------------------------------------
    document.querySelectorAll('.field-block[data-field-type="photo"]').forEach(function (block) {
        var fieldId      = block.dataset.fieldId;
        var itemCode     = block.dataset.itemCode;
        var allowMulti   = block.dataset.allowMultiple === '1';
        var containerId  = 'photo-container-' + fieldId;
        var triggerSel   = '#photo-trigger-' + fieldId;

        PhotoCapture.init({
            triggerSelector: triggerSel,
            fieldId:         fieldId,
            itemCode:        itemCode,
            uploadUrl:       basePath + '/api/upload_photo.php',
            deleteUrl:       basePath + '/api/delete_photo.php',
            containerId:     containerId,
            allowMultiple:   allowMulti
        });

        // Attach delete handlers to already-rendered photo thumbnails
        var container = document.getElementById(containerId);
        if (container) {
            container.querySelectorAll('.photo-delete-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    deletePrerenderedPhoto(
                        btn.dataset.imageId,
                        itemCode,
                        btn.closest('.photo-thumb-wrap'),
                        allowMulti,
                        triggerSel
                    );
                });
            });
        }

        // Hide trigger if single-upload and photo already exists
        if (!allowMulti) {
            if (container && container.querySelector('.photo-thumb-wrap')) {
                var trigger = document.querySelector(triggerSel);
                if (trigger) { trigger.style.display = 'none'; }
            }
        }
    });

    // ---------------------------------------------------------------
    // Document fields
    // ---------------------------------------------------------------
    document.querySelectorAll('.field-block[data-field-type="document"]').forEach(function (block) {
        var fieldId     = block.dataset.fieldId;
        var itemCode    = block.dataset.itemCode;
        var allowMulti  = block.dataset.allowMultiple === '1';
        var containerId = 'doc-container-' + fieldId;
        var triggerSel  = '#doc-trigger-' + fieldId;
        var inputId     = 'doc-file-' + fieldId;

        var trigger = document.getElementById('doc-trigger-' + fieldId);
        var fileInput = document.getElementById(inputId);

        if (trigger && fileInput) {
            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                fileInput.click();
            });

            fileInput.addEventListener('change', function () {
                if (!this.files || !this.files[0]) { return; }
                uploadDocument(this.files[0], fieldId, itemCode, containerId, allowMulti, triggerSel);
                this.value = '';  // Reset so the same file can be re-selected if needed
            });
        }

        // Attach delete handlers to already-rendered documents
        var container = document.getElementById(containerId);
        if (container) {
            container.querySelectorAll('.doc-delete-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    deleteDocument(btn.dataset.docId, itemCode, btn.closest('li'));
                });
            });
        }

        // Hide trigger if single-upload and a doc exists
        if (!allowMulti && container && container.querySelector('li')) {
            if (trigger) { trigger.style.display = 'none'; }
        }
    });

    // ---------------------------------------------------------------
    // Signature fields
    // ---------------------------------------------------------------
    document.querySelectorAll('.field-block[data-field-type="signature"]').forEach(function (block) {
        var fieldId            = block.dataset.fieldId;
        var itemCode           = block.dataset.itemCode;
        var allowMulti         = block.dataset.allowMultiple === '1';
        var requirePrinted     = block.dataset.requirePrintedName === '1';
        var instructions       = block.dataset.instructions || 'Sign in the box below';
        var containerId        = 'sig-container-' + fieldId;
        var triggerSel         = '#sig-trigger-' + fieldId;

        SignatureCapture.init({
            triggerSelector:    triggerSel,
            fieldId:            fieldId,
            itemCode:           itemCode,
            saveUrl:            basePath + '/api/save_signature.php',
            deleteUrl:          basePath + '/api/delete_signature.php',
            containerId:        containerId,
            instructions:       instructions,
            requirePrintedName: requirePrinted,
            allowMultiple:      allowMulti
        });

        // Attach delete handlers to already-rendered signatures
        var container = document.getElementById(containerId);
        if (container) {
            container.querySelectorAll('.sig-delete-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    deleteSignature(btn.dataset.sigId, itemCode, btn.closest('.signature-preview'), allowMulti, triggerSel);
                });
            });
        }

        // Hide trigger if single-capture and a signature already exists
        if (!allowMulti && container && container.querySelector('.signature-preview')) {
            var trigger = document.querySelector(triggerSel);
            if (trigger) { trigger.style.display = 'none'; }
        }
    });

    // ---------------------------------------------------------------
    // Document upload helper
    // ---------------------------------------------------------------
    function uploadDocument(file, fieldId, itemCode, containerId, allowMulti, triggerSel) {
        var formData = new FormData();
        formData.append('document',  file);
        formData.append('field_id',  fieldId);
        formData.append('item_code', itemCode);

        fetch(basePath + '/api/upload_document.php', { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    addDocumentRow(data.doc_id, data.url, data.filename, containerId, itemCode, allowMulti, triggerSel);
                } else {
                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(function (err) { alert('Upload request failed: ' + err); });
    }

    function addDocumentRow(docId, url, filename, containerId, itemCode, allowMulti, triggerSel) {
        var container = document.getElementById(containerId);
        if (!container) { return; }

        var li  = document.createElement('li');
        var link = document.createElement('a');
        link.href   = url;
        link.target = '_blank';
        link.rel    = 'noopener';
        link.textContent = filename;

        var del = document.createElement('button');
        del.type      = 'button';
        del.className = 'doc-delete-btn';
        del.dataset.docId = docId;
        del.textContent   = '✕';
        del.addEventListener('click', function () {
            deleteDocument(docId, itemCode, li);
        });

        li.appendChild(link);
        li.appendChild(del);
        container.appendChild(li);

        if (!allowMulti) {
            var trigger = document.querySelector(triggerSel);
            if (trigger) { trigger.style.display = 'none'; }
        }
    }

    function deleteDocument(docId, itemCode, liEl) {
        if (!confirm('Remove this document?')) { return; }
        var formData = new FormData();
        formData.append('doc_id',    docId);
        formData.append('item_code', itemCode);

        fetch(basePath + '/api/delete_document.php', { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    if (liEl) { liEl.remove(); }
                } else {
                    alert('Delete failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(function (err) { alert('Delete request failed: ' + err); });
    }

    // ---------------------------------------------------------------
    // Signature delete helper (for pre-rendered signatures)
    // ---------------------------------------------------------------
    function deleteSignature(sigId, itemCode, wrapEl, allowMulti, triggerSel) {
        if (!confirm('Remove this signature?')) { return; }
        var formData = new FormData();
        formData.append('sig_id',    sigId);
        formData.append('item_code', itemCode);

        fetch(basePath + '/api/delete_signature.php', { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    if (wrapEl) { wrapEl.remove(); }
                    if (!allowMulti) {
                        var trigger = document.querySelector(triggerSel);
                        if (trigger) { trigger.style.display = ''; }
                    }
                } else {
                    alert('Delete failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(function (err) { alert('Delete request failed: ' + err); });
    }

    // ---------------------------------------------------------------
    // Photo delete helper (for pre-rendered photo thumbnails)
    // ---------------------------------------------------------------
    function deletePrerenderedPhoto(imageId, itemCode, wrapEl, allowMulti, triggerSel) {
        if (!confirm('Remove this photo?')) { return; }
        var formData = new FormData();
        formData.append('image_id',  imageId);
        formData.append('item_code', itemCode);

        fetch(basePath + '/api/delete_photo.php', { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    if (wrapEl) { wrapEl.remove(); }
                    if (!allowMulti) {
                        var trigger = document.querySelector(triggerSel);
                        if (trigger) { trigger.style.display = ''; }
                    }
                } else {
                    alert('Delete failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(function (err) { alert('Delete request failed: ' + err); });
    }
});
