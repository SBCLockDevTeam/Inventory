<?php
/**
 * AJAX endpoint: send an ESC/P label print job to a network printer.
 *
 * POST params:
 *   printer_id  — INT id from the printers table
 *   item_name   — text to print as the item name
 *   description — text to print as the item description
 *
 * Returns JSON: { success: bool, error?: string }
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/client_helper.php';
require_once __DIR__ . '/../lib/form_helpers.php';

header('Content-Type: application/json');

// ESC/P printer timing and formatting constants
const PRINTER_CONNECT_TIMEOUT   = 5;    // seconds to wait for TCP connection
const PRINTER_DELAY_MICROSECONDS = 300000; // 300 ms pause before/after data send
const LABEL_LINE_WIDTH          = 40;   // characters per line on the label

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$printer_id  = (int)FormHelper::getPost('printer_id', '0');
// Use raw POST values for the text that goes to the printer so that
// HTML entities (e.g. "&amp;") are never sent to the ESC/P device.
$item_name   = trim($_POST['item_name']   ?? '');
$description = trim($_POST['description'] ?? '');

if ($printer_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid printer ID']);
    exit;
}

$printer = DatabaseHelper::queryOne(
    "SELECT id, name, ip_address, port, is_active FROM printers WHERE id = ?",
    [$printer_id]
);

if (!$printer) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Printer not found']);
    exit;
}

if (!$printer['is_active']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Printer is not active']);
    exit;
}

// Build the ESC/P payload
// ESC @ initialises the printer, ESC k sets the font, FF ejects the label
$payload  = "\x1b@";                                     // Initialize printer
$payload .= "\x1b\x6b\x00";                             // Select font (Roman)
$payload .= wordwrap($item_name,   LABEL_LINE_WIDTH, "\r\n", true) . "\r\n";
if (trim($description) !== '') {
    $payload .= wordwrap($description, LABEL_LINE_WIDTH, "\r\n", true) . "\r\n";
}
$payload .= "\x0c";                                      // Form feed – eject label

// Open a TCP connection to the printer (5-second timeout)
$fp = @fsockopen($printer['ip_address'], (int)$printer['port'], $errno, $errstr, PRINTER_CONNECT_TIMEOUT);
if (!$fp) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error'   => 'Cannot reach printer: ' . $errstr . ' (' . $errno . ')',
    ]);
    exit;
}

// Give the printer a moment to be ready, then send
usleep(PRINTER_DELAY_MICROSECONDS);
fwrite($fp, $payload);
usleep(PRINTER_DELAY_MICROSECONDS);
fclose($fp);

echo json_encode(['success' => true]);
