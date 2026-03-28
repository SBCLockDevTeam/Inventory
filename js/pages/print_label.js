/**
 * Printer selector and print-label functionality for the item view page.
 *
 * - Reads the list of active printers from the DOM (#printer-select).
 * - The server pre-selects the user's saved printer (or the system default
 *   when no preference has been saved yet).
 * - When the user changes their printer choice, the new selection is saved
 *   to the server via AJAX (api/set_printer.php) so it persists across
 *   browsers and devices.
 * - The "Print Label" button sends an AJAX POST to /api/print.php and
 *   shows a brief status message in the #print-status span.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var select   = document.getElementById('printer-select');
        var printBtn = document.getElementById('btn-print-label');
        var status   = document.getElementById('print-status');

        if (!select || !printBtn) {
            return;
        }

        // Persist selection server-side whenever the user changes it
        select.addEventListener('change', function () {
            var printerId = select.value;
            if (!printerId) { return; }

            fetch(BASE_PATH + '/api/set_printer.php', {
                method: 'POST',
                body: (function () {
                    var fd = new FormData();
                    fd.append('printer_id', printerId);
                    return fd;
                }()),
            })
            .catch(function (err) {
                // Log silently — failure to save preference is non-critical
                console.warn('Could not save printer preference:', String(err));
            });
        });

        // Send print job on button click
        printBtn.addEventListener('click', function () {
            var printerId   = select.value;
            var itemName    = printBtn.getAttribute('data-item-name')    || '';
            var description = printBtn.getAttribute('data-description')  || '';

            if (!printerId) {
                window.showError('Please select a printer first.', 'error');
                return;
            }

            printBtn.disabled = true;
            showPrintStatus('Sending to printer…', 'info');
            window.clearErrors();

            var formData = new FormData();
            formData.append('printer_id',  printerId);
            formData.append('item_name',   itemName);
            formData.append('description', description);

            fetch(BASE_PATH + '/api/print.php', {
                method: 'POST',
                body:   formData,
            })
            .then(function (resp) { return resp.json(); })
            .then(function (data) {
                if (data.success) {
                    showPrintStatus('✓ Sent to printer.', 'success');
                } else {
                    clearPrintStatus();
                    window.showError('✗ ' + (data.error || 'Print failed.'), 'error');
                }
            })
            .catch(function (err) {
                clearPrintStatus();
                window.showError('✗ Request failed: ' + err.message, 'error');
            })
            .finally(function () {
                printBtn.disabled = false;
            });
        });

        function showPrintStatus(msg, type) {
            if (!status) { return; }
            status.textContent = msg;
            status.className   = 'print-status print-status--' + type + ' print-status--visible';
        }

        function clearPrintStatus() {
            if (!status) { return; }
            status.textContent = '';
            status.className   = 'print-status';
        }
    });
}());
