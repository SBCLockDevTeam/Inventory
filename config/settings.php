<?php
// Non-sensitive application settings.
// Sensitive credentials (DB password etc.) live in config/secrets.php
// which is excluded from Git.

return [
    // Base URL for all links and assets (no trailing slash)
    'base_url'        => 'https://SBCQR.com/qr',

    // Application version shown in footer
    'app_version'     => '0.1.0',

    // Contact / footer email
    'contact_email'   => 'info@securitybuildingcontrols.com',

    // Path (relative to project root) where uploaded files are stored
    'upload_path'     => __DIR__ . '/../assets/uploads',

    // Maximum upload size in bytes (default 10 MB)
    'max_upload_size' => 10 * 1024 * 1024,
];