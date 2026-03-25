<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/logger.php';

function app_settings(): array
{
    static $settings = null;
    if ($settings !== null) return $settings;

    $loaded = include __DIR__ . '/../config/settings.php';
    $settings = is_array($loaded) ? $loaded : [];
    return $settings;
}

function db(): Database
{
    static $db = null;
    if ($db instanceof Database) return $db;

    $db = new Database();
    return $db;
}

function add_error(string $severity, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    $severity = strtolower(trim($severity));
    if (!in_array($severity, ['error', 'warning', 'notice'], true)) {
        $severity = 'error';
    }

    $_SESSION['app_errors'] ??= [];
    $_SESSION['app_errors'][] = ['severity' => $severity, 'message' => $message];
}

/**
 * @return array<int, array{severity:string, message:string}>
 */
function get_errors(bool $clear = true): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    $errors = $_SESSION['app_errors'] ?? [];
    if ($clear) $_SESSION['app_errors'] = [];

    return is_array($errors) ? $errors : [];
}

function log_exception(Throwable $e, string $context = ''): void
{
    try {
        $logFile = __DIR__ . '/../logs/activity.log';
        if (!is_dir(dirname($logFile))) {
            @mkdir(dirname($logFile), 0775, true);
        }

        $logger = new Logger($logFile);
        $logger->log('exception', Logger::LOG_LEVEL_ERROR, [
            'context' => $context,
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    } catch (Throwable $ignored) {
        // Never break the app due to logging.
    }
}