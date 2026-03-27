/**
 * photo_capture.js
 * Reusable photo-capture widget.
 *
 * Exposes one public function: PhotoCapture.init(config)
 *
 * config = {
 *   triggerSelector:  CSS selector for the "Add Photo" button(s)
 *   fieldId:          int — item_field.id
 *   itemCode:         string — 10-hex item public_code
 *   uploadUrl:        string — URL of api/upload_photo.php
 *   deleteUrl:        string — URL of api/delete_photo.php
 *   containerId:      string — id of element to inject thumbnails into
 *   allowMultiple:    bool
 * }
 */
var PhotoCapture = (function () {

    var _overlay    = null;
    var _cfg        = {};

    // ------------------------------------------------------------------
    // Build the choice overlay (one per page, reused across all triggers)
    // ------------------------------------------------------------------
    function buildOverlay() {
        if (document.getElementById('photo-choice-overlay')) {
            return;
        }
        var el = document.createElement('div');
        el.id        = 'photo-choice-overlay';
        el.className = 'photo-choice-overlay';
        el.innerHTML =
            '<div class="photo-choice-dialog">' +
            '  <h3>Add Photo</h3>' +
            '  <div class="photo-choice-btns">' +
            '    <button id="photo-choice-camera" class="btn btn-primary">📷 Take Photo with Camera</button>' +
            '    <button id="photo-choice-browse" class="btn btn-secondary">🖼 Browse for Image File</button>' +
            '  </div>' +
            '  <button class="photo-choice-cancel" id="photo-choice-cancel">Cancel</button>' +
            '</div>';
        document.body.appendChild(el);
        _overlay = el;

        document.getElementById('photo-choice-camera').addEventListener('click', function () {
            closeOverlay();
            openCamera();
        });
        document.getElementById('photo-choice-browse').addEventListener('click', function () {
            closeOverlay();
            openFileBrowser();
        });
        document.getElementById('photo-choice-cancel').addEventListener('click', closeOverlay);
        el.addEventListener('click', function (e) {
            if (e.target === el) { closeOverlay(); }
        });
    }

    function openOverlay(cfg) {
        _cfg = cfg;
        buildOverlay();
        document.getElementById('photo-choice-overlay').classList.add('active');
    }
    function closeOverlay() {
        var ov = document.getElementById('photo-choice-overlay');
        if (ov) { ov.classList.remove('active'); }
    }

    // ------------------------------------------------------------------
    // Camera capture via <input capture="environment">
    // ------------------------------------------------------------------
    function openCamera() {
        var inp = document.createElement('input');
        inp.type    = 'file';
        inp.accept  = 'image/*';
        inp.capture = 'environment';
        inp.style.display = 'none';
        document.body.appendChild(inp);
        inp.addEventListener('change', function () {
            if (inp.files && inp.files[0]) {
                uploadFile(inp.files[0]);
            }
            document.body.removeChild(inp);
        });
        inp.click();
    }

    // ------------------------------------------------------------------
    // Browse for existing image file
    // ------------------------------------------------------------------
    function openFileBrowser() {
        var inp = document.createElement('input');
        inp.type   = 'file';
        inp.accept = 'image/*';
        inp.style.display = 'none';
        document.body.appendChild(inp);
        inp.addEventListener('change', function () {
            if (inp.files && inp.files[0]) {
                uploadFile(inp.files[0]);
            }
            document.body.removeChild(inp);
        });
        inp.click();
    }

    // ------------------------------------------------------------------
    // Upload the selected / captured file via AJAX
    // ------------------------------------------------------------------
    function uploadFile(file) {
        var formData = new FormData();
        formData.append('photo',     file);
        formData.append('field_id',  _cfg.fieldId);
        formData.append('item_code', _cfg.itemCode);

        fetch(_cfg.uploadUrl, { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    addThumbnail(data.image_id, data.url);
                } else {
                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(function (err) {
                alert('Upload request failed: ' + err);
            });
    }

    // ------------------------------------------------------------------
    // Inject a new thumbnail into the container
    // ------------------------------------------------------------------
    function addThumbnail(imageId, url) {
        var container = document.getElementById(_cfg.containerId);
        if (!container) { return; }

        var wrap = document.createElement('div');
        wrap.className = 'photo-thumb-wrap';
        wrap.dataset.imageId = imageId;

        var img = document.createElement('img');
        img.src = url;
        img.alt = 'Photo';
        img.addEventListener('click', function () {
            var w = window.open(url, '_blank');
            if (w) { w.opener = null; }
        });

        var del = document.createElement('button');
        del.type      = 'button';
        del.className = 'photo-delete-btn';
        del.title     = 'Remove photo';
        del.textContent = '✕';
        del.addEventListener('click', function () { deletePhoto(imageId, wrap); });

        wrap.appendChild(img);
        wrap.appendChild(del);
        container.appendChild(wrap);

        // Hide the trigger if single-upload and a photo is now present
        if (!_cfg.allowMultiple) {
            var trigger = document.querySelector(_cfg.triggerSelector);
            if (trigger) { trigger.style.display = 'none'; }
        }
    }

    // ------------------------------------------------------------------
    // Delete a photo via AJAX
    // ------------------------------------------------------------------
    function deletePhoto(imageId, wrapEl) {
        if (!confirm('Remove this photo?')) { return; }

        var formData = new FormData();
        formData.append('image_id',  imageId);
        formData.append('item_code', _cfg.itemCode);

        fetch(_cfg.deleteUrl, { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    if (wrapEl) { wrapEl.remove(); }
                    // Restore the trigger button if single-upload
                    if (!_cfg.allowMultiple) {
                        var trigger = document.querySelector(_cfg.triggerSelector);
                        if (trigger) { trigger.style.display = ''; }
                    }
                } else {
                    alert('Delete failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(function (err) {
                alert('Delete request failed: ' + err);
            });
    }

    // ------------------------------------------------------------------
    // Public init: wire up trigger button(s)
    // ------------------------------------------------------------------
    function init(cfg) {
        buildOverlay();
        var triggers = document.querySelectorAll(cfg.triggerSelector);
        triggers.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openOverlay(cfg);
            });
        });
    }

    return { init: init };
}());
