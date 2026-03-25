<?php

// logs.php

// Display activity logs

// Database connection (if applicable)
// $db = new PDO('mysql:host=localhost;dbname=your_db', 'username', 'password');

// Fetch logs from the database or a file
$logs = [
    ['timestamp' => '2026-03-25 05:15:29', 'user' => 'SBCLockDevTeam', 'action' => 'User logged in'],
    // Add more log entries as needed
];

// Display logs in a readable format
foreach ($logs as $log) {
    echo "[{$log['timestamp']}] User: {$log['user']} - Action: {$log['action']}\n";
}

?>
