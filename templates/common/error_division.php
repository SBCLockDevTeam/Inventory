<?php
/**
 * templates/common/error_division.php
 *
 * Flash-message banner. Include this template immediately after the opening
 * main / content wrapper on every page so users always see pending messages.
 *
 * Relies on get_errors() from lib/bootstrap.php, which reads (and clears) the
 * $_SESSION['app_errors'] array that was populated by add_error().
 *
 * Each entry in the array must be:
 *   ['severity' => 'error'|'warning'|'notice', 'message' => string]
 *
 * Usage:
 *   include __DIR__ . '/../templates/common/error_division.php';
 *
 * To inject errors inline (without a redirect) instead of via the session,
 * set $inlineErrors before including this file:
 *   $inlineErrors = [['severity' => 'error', 'message' => 'Something went wrong.']];
 */

$_flashErrors = function_exists('get_errors') ? get_errors(true) : [];

if (!empty($inlineErrors) && is_array($inlineErrors)) {
    $_flashErrors = array_merge($_flashErrors, $inlineErrors);
}

if (empty($_flashErrors)) {
    return;
}

$_severityMap = [
    'error'   => ['class' => 'alert--error',  'role' => 'alert',  'icon' => '&#10005;', 'label' => 'Error'],
    'warning' => ['class' => 'alert--warning', 'role' => 'alert',  'icon' => '&#9888;',  'label' => 'Warning'],
    'notice'  => ['class' => 'alert--notice',  'role' => 'status', 'icon' => '&#8505;',  'label' => 'Notice'],
];
?>

<div class="error-division" aria-live="polite">
<?php foreach ($_flashErrors as $_err):
    $sev  = htmlspecialchars($_err['severity'] ?? 'error', ENT_QUOTES, 'UTF-8');
    $msg  = htmlspecialchars($_err['message']  ?? '',      ENT_QUOTES, 'UTF-8');
    $meta = $_severityMap[$sev] ?? $_severityMap['error'];
?>
    <div class="alert <?= $meta['class'] ?>"
         role="<?= $meta['role'] ?>"
         aria-label="<?= $meta['label'] ?>">
        <span class="alert__icon" aria-hidden="true"><?= $meta['icon'] ?></span>
        <span class="alert__message"><?= $msg ?></span>
        <button type="button"
                class="alert__dismiss"
                aria-label="Dismiss <?= $meta['label'] ?>"
                onclick="this.closest('.alert').remove()">&#10005;</button>
    </div>
<?php endforeach; ?>
</div>

<?php
unset($_flashErrors, $_severityMap, $_err, $sev, $msg, $meta);
?>
