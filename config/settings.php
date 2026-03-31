<?php // URL/Path constants - ALL relative to /qr/ base 
const BASE_PATH = '/qr';  
// URL prefix, no trailing slash 
const BASE_URL = 'https://sbcqr.com/qr';  
// Full domain URL, no trailing slash 
const SERVER_ROOT = '/var/www/html/sbcqr/qr';  
// Absolute server path, no trailing slash 

// All asset paths use BASE_PATH 
const ASSETS_PATH = BASE_PATH . '/assets/'; 
const CSS_PATH = BASE_PATH . '/css/'; 
const JS_PATH = BASE_PATH . '/js/'; 
const UPLOAD_PATH = BASE_PATH . '/uploads/'; 

// All server paths use SERVER_ROOT 
const CONFIG_PATH = SERVER_ROOT . '/config/'; 
const LIB_PATH = SERVER_ROOT . '/lib/'; 
const TEMPLATES_PATH = SERVER_ROOT . '/templates/'; 

// Outbound SMTP relay (Microsoft 365 SMTP AUTH - client submission)
// Credentials (EMAIL_USER / EMAIL_PASS) are stored in config/secrets.php
const SMTP_HOST       = 'smtp.office365.com';
const SMTP_PORT       = 587;              // STARTTLS
const SMTP_ENCRYPTION = 'tls';            // STARTTLS
const SMTP_FROM_ADDR  = 'noreply@sbcqr.com';  // keep if this mailbox/sender is allowed in M365
const SMTP_FROM_NAME  = 'QR Inventory System';
const SMTP_TO         = 'info@securitybuildingcontrols.com';
?>
