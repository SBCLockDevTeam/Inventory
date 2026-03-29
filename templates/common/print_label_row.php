<?php
/**
 * Print Label row – inline widget for the form-actions area on add / edit / clone pages.
 *
 * Expected variables injected by the including page:
 *   $printers            array   Active printer list from PrinterHelper::getActivePrinters()
 *   $selected_printer_id int     Pre-selected printer ID from PrinterHelper::getSelectedPrinterId()
 *
 * Renders nothing when $printers is empty (no active printers configured).
 */
if (empty($printers)) {
    return;
}
?>
<span class="print-label-row">
    <select id="form-printer-select" name="printer_id">
        <?php foreach ($printers as $p): ?>
        <option value="<?php echo (int)$p['id']; ?>"
            <?php echo ((int)$p['id'] === $selected_printer_id) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($p['name']); ?>
        </option>
        <?php endforeach; ?>
    </select>
    <label class="checkbox-label">
        <input type="checkbox" name="print_label" id="print_label">
        <span>Print Label</span>
    </label>
</span>
