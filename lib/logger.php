<?php

class Logger {
    const LOG_LEVEL_INFO = 'INFO';
    const LOG_LEVEL_WARNING = 'WARNING';
    const LOG_LEVEL_ERROR = 'ERROR';

    private $logFile;

    public function __construct($logFile = 'activity.log') {
        $this->logFile = $logFile;
    }

    public function log($action, $level, $data) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "["] . $timestamp . ["] [$level] $action: " . json_encode($data) . "\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
}