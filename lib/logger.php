<?php
declare(strict_types=1);

/**
 * Simple append-only file logger.
 * Writes JSON lines to the given log file path.
 *
 * Usage:
 *   $logger = new Logger('/var/www/html/sbcqr/qr/logs/activity.log');
 *   $logger->log('item_created', Logger::LEVEL_INFO, ['item' => 'a1b2c3d4e5']);
 */
class Logger
{
    const LEVEL_INFO  = 'info';
    const LEVEL_WARN  = 'warning';
    const LEVEL_ERROR = 'error';

    // Keep backwards compat with bootstrap.php which uses LOG_LEVEL_ERROR
    const LOG_LEVEL_ERROR = self::LEVEL_ERROR;

    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * Append a log entry.
     *
     * @param string $event   Short event name, e.g. 'item_created'
     * @param string $level   One of the LEVEL_* constants
     * @param array  $context Arbitrary key-value context data
     */
    public function log(string $event, string $level, array $context = []): void
    {
        $entry = json_encode([
            'ts'      => date('c'),        // ISO-8601 timestamp
            'level'   => $level,
            'event'   => $event,
            'context' => $context,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Silently swallow write failures — logging must never break the app
        @file_put_contents($this->path, $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}