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

// Outbound SMTP relay (foreign mail server)
// Credentials (EMAIL_USER / EMAIL_PASS) are stored in config/secrets.php
const SMTP_HOST       = 'smtp.ionos.com'; // SMTP server hostname
const SMTP_PORT       = 587;              // 587 = STARTTLS, 465 = SSL/TLS
const SMTP_ENCRYPTION = 'tls';            // 'tls' (STARTTLS) or 'ssl'
const SMTP_FROM_ADDR  = 'sbcqr@wifld.com';
const SMTP_FROM_NAME  = 'QR Inventory System';
const SMTP_TO         = 'lockdevteam@securitybuildingcontrols.com';
?>
