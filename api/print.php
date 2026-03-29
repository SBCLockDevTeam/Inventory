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

// ESC/P label formatting constants
const LABEL_LINE_WIDTH = 40;   // characters per line on the label

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
    "SELECT id, name, host, port, is_active FROM printers WHERE id = ?",
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


// ESC i a NUL  — switch Brother printer into ESC/P mode (required after power-on)
// ESC @        — initialise / reset printer
// ESC k        — select font
// FF           — form feed / eject label
$payload  = "\x1b\x69\x61\x00";                         // Brother mode switch (ESC i a NUL)
$payload .= "\x1b@";                                    // Initialize printer
$payload .= "\x1b\x6b\x0b";                             // Select font (Letter Gothic Bold)
//$payload .= "\x1b\x45";								  // Turn on bold
//$payload .= "\x1B\x46";							      // Turn off bold
// TestString = TestString & Chr(27) & "(c" & Chr(2) & Chr(0) & Chr(199) & Chr(6)
$payload .= "\x1B(c\x02\x00\x88\x03";					




$payload .= "\x1B$\x08\x00";							// Absolute Horozontal Position
$payload .= "\x1B(V\x02\x00\x0C\x00";					// Absolute Vertical Position
//$payload .= "\x1Bk\x0B\x1BX\x00&\x00";
$payload .= "\x1Bk\x0B\x1BX\x00\x30\x00";


$payload .= $item_name;


$payload .= "\x1B$\xA0\x00";							// Absolute Horozontal Position
$payload .= "\x1B(V\x02\x00\x60\x00";					// Absolute Vertical Position
$payload .= "\x1Bk\x0B\x1BX\x00&\x00";
//$payload .= "\x1Bk\x0B\x1BX\x00\x30\x00";

	
if (trim($description) !== '') {
    $payload .= wordwrap($description, LABEL_LINE_WIDTH, "\r\n", true) . "\r\n";
}

$payload .= "\x1B$\x01\x00";							// Absolute Horozontal Position
$payload .= "\x1B(V\x02\x00\x48\x00";					// Absolute Vertical Position
$payload .="\x1b\x69\x51\x07\x02\x00\x00\x00\x00\x04\x00";
$payload .="https://sbcqr.com/qr/?Q=792ddc3f5c";
$payload .="\x5c\x5c\x5c";


$payload .= "\x0c";                                      // Form feed – eject label

// Use the compiled binary helper to send the print job.
// PHP's fsockopen() has had intermittent failures resolving hostnames such
// as pierround.com in certain server configurations; the binary uses
// getaddrinfo() which handles both hostnames and IP addresses reliably.
$binary = realpath(__DIR__ . '/../bin/printer');
if (!$binary || !is_executable($binary)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Printer helper binary not found or not executable']);
    exit;
}

$cmd = escapeshellarg($binary)
     . ' ' . escapeshellarg($printer['host'])
     . ' ' . escapeshellarg((string)(int)$printer['port']);

$descriptors = [
    0 => ['pipe', 'r'],  // stdin  — we write the ESC/P payload here
    1 => ['pipe', 'w'],  // stdout — discard
    2 => ['pipe', 'w'],  // stderr — capture for error reporting
];

$proc = proc_open($cmd, $descriptors, $pipes);
if (!is_resource($proc)) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Could not launch printer helper']);
    exit;
}

fwrite($pipes[0], $payload);
fclose($pipes[0]);

$stderr_output = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exit_code = proc_close($proc);

if ($exit_code !== 0) {
    http_response_code(502);
    error_log('Printer helper failed: ' . trim($stderr_output));
    echo json_encode([
        'success' => false,
        'error'   => 'Print failed: Could not connect to printer.',
    ]);
    exit;
}

echo json_encode(['success' => true]);
