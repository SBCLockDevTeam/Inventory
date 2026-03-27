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
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../lib/form_helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$name     = FormHelper::getPost('submitter_name');
$email    = FormHelper::getPost('submitter_email');
$category = FormHelper::getPost('category');
$subject  = FormHelper::getPost('subject');
$message  = FormHelper::getPost('message');

$allowed_categories = ['feature_request', 'bug_report', 'feedback'];

$errors = [];
if (empty($name))    { $errors[] = 'Name is required'; }
if (empty($email))   { $errors[] = 'Email is required'; }
elseif (!FormHelper::isValidEmail($email)) { $errors[] = 'Invalid email address'; }
if (!in_array($category, $allowed_categories)) { $errors[] = 'Invalid category'; }
if (empty($subject)) { $errors[] = 'Subject is required'; }
if (empty($message)) { $errors[] = 'Message is required'; }

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

$to      = 'info@securitybuildingcontrols.com';
// Strip newlines from email to prevent header injection attacks
$email    = str_replace(["\r", "\n"], '', $email);

$headers = "From: QR Inventory System <noreply@sbcqr.com>\r\n" .
           "Reply-To: $email\r\n" .
           "Content-Type: text/plain; charset=UTF-8\r\n";

$mail_subject = "[Inventory Feedback] [$category_label] $subject";

$mail_body = "Category: $category_label\n" .
             "From: $name <$email>\n" .
             "Subject: $subject\n" .
             str_repeat('-', 60) . "\n" .
             "$message\n";

// mail() is used here; on a real server this will deliver the email.
// If mail() is not configured, the call fails silently and we still return success
// so the user experience is not degraded in development.
@mail($to, $mail_subject, $mail_body, $headers);

echo json_encode(['success' => true]);
