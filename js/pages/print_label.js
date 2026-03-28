/**
 * Printer selector and print-label functionality for the item view page.
 *
 * - Reads the list of active printers from the DOM (#printer-select).
 * - Persists the user's selected printer in localStorage so the choice
 *   survives page navigation and browser restarts.
 * - The default printer (is_default = 1) is used when no localStorage
 *   preference exists yet.
 * - The "Print Label" button sends an AJAX POST to /api/print.php and
 *   shows a brief status message.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'sbcinv_selected_printer';

    document.addEventListener('DOMContentLoaded', function () {

        var select   = document.getElementById('printer-select');
        var printBtn = document.getElementById('btn-print-label');
        var status   = document.getElementById('print-status');

        if (!select || !printBtn) {
            return;
        }

        // Restore the previously chosen printer, falling back to the default
        var stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            // Make sure the stored value still exists in the dropdown
            var found = false;
            for (var i = 0; i < select.options.length; i++) {
                if (select.options[i].value === stored) {
                    select.value = stored;
                    found = true;
                    break;
                }
            }
            if (!found) {
                // Stored printer no longer available; clear stale value
                localStorage.removeItem(STORAGE_KEY);
            }
        }

        // Persist selection whenever the user changes it
        select.addEventListener('change', function () {
            localStorage.setItem(STORAGE_KEY, select.value);
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
                    window.showError('✓ Sent to printer.', 'notice');
                } else {
                    window.showError(data.error || 'Print failed.', 'error');
                }
            })
            .catch(function (err) {
                window.showError('Request failed: ' + err.message, 'error');
            })
            .finally(function () {
                printBtn.disabled = false;
                clearPrintStatus();
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
