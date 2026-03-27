/**
 * signature_capture.js
 * Reusable canvas-based signature widget.
 *
 * Exposes one public function: SignatureCapture.init(config)
 *
 * config = {
 *   triggerSelector:   CSS selector for the "Sign" button(s)
 *   fieldId:           int — item_field.id
 *   itemCode:          string — 10-hex item public_code
 *   saveUrl:           string — URL of api/save_signature.php
 *   deleteUrl:         string — URL of api/delete_signature.php
 *   containerId:       string — id of element to inject previews into
 *   instructions:      string — text shown inside the signature dialog
 *   requirePrintedName: bool
 *   allowMultiple:     bool
 * }
 */
var SignatureCapture = (function () {

    var _canvas   = null;
    var _ctx      = null;
    var _drawing  = false;
    var _cfg      = {};

    // ------------------------------------------------------------------
    // Build and attach the signature overlay (one per page, shared)
    // ------------------------------------------------------------------
    function buildOverlay() {
        if (document.getElementById('signature-overlay')) { return; }

        var el = document.createElement('div');
        el.id        = 'signature-overlay';
        el.className = 'signature-overlay';
        el.innerHTML =
            '<div class="signature-dialog">' +
            '  <h3>Signature</h3>' +
            '  <p class="signature-instructions" id="sig-instructions"></p>' +
            '  <div class="signature-canvas-wrap">' +
            '    <canvas id="signature-canvas" width="480" height="200"></canvas>' +
            '  </div>' +
            '  <div class="signature-printed-name-group" id="sig-printed-group" style="display:none;">' +
            '    <label for="sig-printed-name">Printed Name <span class="required">*</span></label>' +
            '    <input type="text" id="sig-printed-name" placeholder="Type your full name" style="width:100%;margin-top:0.25rem;">' +
            '  </div>' +
            '  <div class="signature-actions">' +
            '    <button class="btn btn-primary"  id="sig-save-btn">Save Signature</button>' +
            '    <button class="btn btn-secondary" id="sig-clear-btn">Clear</button>' +
            '    <button class="btn btn-secondary" id="sig-cancel-btn">Cancel</button>' +
            '  </div>' +
            '</div>';

        document.body.appendChild(el);

        _canvas = document.getElementById('signature-canvas');
        _ctx    = _canvas.getContext('2d');

        // Drawing events — mouse
        _canvas.addEventListener('mousedown',  startDraw);
        _canvas.addEventListener('mousemove',  draw);
        _canvas.addEventListener('mouseup',    stopDraw);
        _canvas.addEventListener('mouseleave', stopDraw);

        // Drawing events — touch
        _canvas.addEventListener('touchstart', function (e) { e.preventDefault(); startDraw(e.touches[0]); }, { passive: false });
        _canvas.addEventListener('touchmove',  function (e) { e.preventDefault(); draw(e.touches[0]); },      { passive: false });
        _canvas.addEventListener('touchend',   stopDraw);

        document.getElementById('sig-save-btn').addEventListener('click',   saveSignature);
        document.getElementById('sig-clear-btn').addEventListener('click',  clearCanvas);
        document.getElementById('sig-cancel-btn').addEventListener('click', closeOverlay);

        el.addEventListener('click', function (e) {
            if (e.target === el) { closeOverlay(); }
        });
    }

    function openOverlay(cfg) {
        _cfg = cfg;
        buildOverlay();

        clearCanvas();

        var instrEl = document.getElementById('sig-instructions');
        if (instrEl) { instrEl.textContent = cfg.instructions || 'Sign in the box below'; }

        var printGroup = document.getElementById('sig-printed-group');
        if (printGroup) {
            printGroup.style.display = cfg.requirePrintedName ? 'block' : 'none';
        }
        var printInput = document.getElementById('sig-printed-name');
        if (printInput) { printInput.value = ''; }

        document.getElementById('signature-overlay').classList.add('active');
    }

    function closeOverlay() {
        var ov = document.getElementById('signature-overlay');
        if (ov) { ov.classList.remove('active'); }
    }

    // ------------------------------------------------------------------
    // Canvas drawing helpers
    // ------------------------------------------------------------------
    function getPos(e) {
        var rect = _canvas.getBoundingClientRect();
        // Scale from CSS pixels to canvas pixels
        var scaleX = _canvas.width  / rect.width;
        var scaleY = _canvas.height / rect.height;
        return {
            x: (e.clientX - rect.left) * scaleX,
            y: (e.clientY - rect.top)  * scaleY
        };
    }

    function startDraw(e) {
        _drawing = true;
        var pos  = getPos(e);
        _ctx.beginPath();
        _ctx.moveTo(pos.x, pos.y);
    }

    function draw(e) {
        if (!_drawing) { return; }
        var pos = getPos(e);
        _ctx.lineWidth   = 2;
        _ctx.lineCap     = 'round';
        _ctx.strokeStyle = '#000';
        _ctx.lineTo(pos.x, pos.y);
        _ctx.stroke();
    }

    function stopDraw() { _drawing = false; }

    function clearCanvas() {
        if (_ctx && _canvas) {
            _ctx.clearRect(0, 0, _canvas.width, _canvas.height);
        }
    }

    function canvasIsBlank() {
        var data = _ctx.getImageData(0, 0, _canvas.width, _canvas.height).data;
        for (var i = 3; i < data.length; i += 4) {
            if (data[i] !== 0) { return false; }
        }
        return true;
    }

    // ------------------------------------------------------------------
    // Save the signature via AJAX
    // ------------------------------------------------------------------
    function saveSignature() {
        if (canvasIsBlank()) {
            alert('Please sign before saving.');
            return;
        }

        var printedName = '';
        if (_cfg.requirePrintedName) {
            var inp = document.getElementById('sig-printed-name');
            printedName = inp ? inp.value.trim() : '';
            if (!printedName) {
                alert('Please enter your printed name.');
                return;
            }
        }

        var dataUrl = _canvas.toDataURL('image/png');
        var formData = new FormData();
        formData.append('signature_data', dataUrl);
        formData.append('field_id',       _cfg.fieldId);
        formData.append('item_code',      _cfg.itemCode);
        formData.append('printed_name',   printedName);

        fetch(_cfg.saveUrl, { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    closeOverlay();
                    addPreview(data.signature_id, data.url, printedName);
                } else {
                    alert('Save failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(function (err) {
                alert('Save request failed: ' + err);
            });
    }

    // ------------------------------------------------------------------
    // Inject signature preview
    // ------------------------------------------------------------------
    function addPreview(sigId, url, printedName) {
        var container = document.getElementById(_cfg.containerId);
        if (!container) { return; }

        var wrap = document.createElement('div');
        wrap.className = 'signature-preview';
        wrap.dataset.sigId = sigId;

        var img = document.createElement('img');
        img.src = url;
        img.alt = 'Signature';

        var del = document.createElement('button');
        del.type      = 'button';
        del.className = 'btn btn-small btn-danger';
        del.style.marginTop = '0.25rem';
        del.textContent = 'Remove';
        del.addEventListener('click', function () { deleteSignature(sigId, wrap); });

        wrap.appendChild(img);
        if (printedName) {
            var nameEl = document.createElement('p');
            nameEl.style.fontSize = '0.85rem';
            nameEl.textContent = 'Signed by: ' + printedName;
            wrap.appendChild(nameEl);
        }
        wrap.appendChild(del);
        container.appendChild(wrap);

        // Hide trigger if single-capture and one signature now exists
        if (!_cfg.allowMultiple) {
            var trigger = document.querySelector(_cfg.triggerSelector);
            if (trigger) { trigger.style.display = 'none'; }
        }
    }

    // ------------------------------------------------------------------
    // Delete a saved signature via AJAX
    // ------------------------------------------------------------------
    function deleteSignature(sigId, wrapEl) {
        if (!confirm('Remove this signature?')) { return; }

        var formData = new FormData();
        formData.append('sig_id',    sigId);
        formData.append('item_code', _cfg.itemCode);

        fetch(_cfg.deleteUrl, { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    if (wrapEl) { wrapEl.remove(); }
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
    // Public init
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
