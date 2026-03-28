<?php
/**
 * Compliance Page
 * Displays compliance information and policies for Security Building Controls.
 */
$page_title = 'Compliance';
require_once __DIR__ . '/../config/settings.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> — QR Inventory</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
</head>
<body>
    <header class="site-header">
        <div class="header-content">
            <div class="header-logo">
                <a href="<?php echo BASE_PATH; ?>/home.php" class="header-site-name">QR Inventory</a>
            </div>
        </div>
    </header>
    <h1>Compliance</h1>
    <div class="body-content">
        <section class="compliance-section">
            <h2>Data Collection &amp; Storage</h2>
            <p>This system collects and stores inventory data including item descriptions, locations, photos, documents, and digital signatures. All data is stored securely on servers operated by Security Building Controls.</p>
        </section>
        <section class="compliance-section">
            <h2>Authentication</h2>
            <p>User authentication is handled via Microsoft Entra ID (Azure Active Directory). No passwords are stored within this application. Access is restricted to authorised personnel only.</p>
        </section>
        <section class="compliance-section">
            <h2>Data Retention</h2>
            <p>Inventory records, logs, and associated files are retained for as long as required to fulfil operational and legal obligations. Records may be deleted upon request by an authorised administrator.</p>
        </section>
        <section class="compliance-section">
            <h2>Access Control</h2>
            <p>Role-based access control is enforced throughout the system. Administrative functions are restricted to users with the admin role. All access is logged for audit purposes.</p>
        </section>
        <section class="compliance-section">
            <h2>Contact</h2>
            <p>For compliance enquiries please contact Security Building Controls at <a href="mailto:info@securitybuildingcontrols.com">info@securitybuildingcontrols.com</a>.</p>
        </section>
    </div>
    <footer>
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> Security Building Controls. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
