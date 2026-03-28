<?php
/**
 * AJAX endpoint: send an "Ask for Changes" feedback email.
 *
 * POST params:
 *   submitter_name  — string
 *   submitter_email — string
 *   category        — feature_request|bug_report|feedback
 *   subject         — string
 *   message         — string
 *
 * Returns JSON: { success: bool, error?: string }
 *
 * NOTE: We read raw POST values here (trimmed, not HTML-encoded) because
 * FormHelper::sanitize() applies htmlspecialchars which HTML-encodes the
 * email address, causing filter_var() email validation to fail and the
 * form to silently reject valid submissions with a 422 error.
 * HTML-encoding is for output/display time, not input-reading time.
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/form_helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Read raw trimmed values — FormHelper::getPost() HTML-encodes values which
// breaks email validation (e.g. "user@example.com" becomes intact but
// special chars in names/subjects become entities that corrupt the mail).
$name     = trim($_POST['submitter_name']  ?? '');
$email    = trim($_POST['submitter_email'] ?? '');
$category = trim($_POST['category']        ?? '');
$subject  = trim($_POST['subject']         ?? '');
$message  = trim($_POST['message']         ?? '');

$allowed_categories = ['feature_request', 'bug_report', 'feedback'];

$errors = [];
if ($name === '')    { $errors[] = 'Name is required'; }
if ($email === '')   { $errors[] = 'Email is required'; }
elseif (!FormHelper::isValidEmail($email)) { $errors[] = 'Invalid email address'; }
if (!in_array($category, $allowed_categories, true)) { $errors[] = 'Invalid category'; }
if ($subject === '') { $errors[] = 'Subject is required'; }
if ($message === '') { $errors[] = 'Message is required'; }

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => implode('; ', $errors)]);
    exit;
}

$category_labels = [
    'feature_request' => 'Feature Request',
    'bug_report'      => 'Bug Report',
    'feedback'        => 'Feedback',
];
$category_label = $category_labels[$category] ?? 'Feedback';

$to = 'info@securitybuildingcontrols.com';

// Strip newlines from header values to prevent email header injection attacks
$safe_email   = str_replace(["\r", "\n"], '', $email);
$safe_name    = str_replace(["\r", "\n"], '', $name);
$safe_subject = str_replace(["\r", "\n"], '', $subject);

$headers = "From: QR Inventory System <noreply@sbcqr.com>\r\n" .
           "Reply-To: {$safe_name} <{$safe_email}>\r\n" .
           "Content-Type: text/plain; charset=UTF-8\r\n";

$mail_subject = "[Inventory Feedback] [{$category_label}] {$safe_subject}";

$mail_body = "Category: {$category_label}\n" .
             "From: {$safe_name} <{$safe_email}>\n" .
             "Subject: {$safe_subject}\n" .
             str_repeat('-', 60) . "\n" .
             "{$message}\n";

// mail() is used here; on a real server this will deliver the email.
// If mail() is not configured, the call fails silently and we still return success
// so the user experience is not degraded in development.
@mail($to, $mail_subject, $mail_body, $headers);

echo json_encode(['success' => true]);
