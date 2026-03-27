<?php require_once __DIR__ . '/../../config/settings.php'; ?>
<footer>
    <div class="footer-content">
        <p>&copy; <?php echo date('Y'); ?> Security Building Controls. All rights reserved.</p>
        <p>Version 1.0.0</p>
        <p><a href="<?php echo BASE_PATH; ?>/compliance/">Compliance</a> | <a href="#" id="ask-changes">Ask for Changes</a></p>
    </div>
</footer>

<?php include __DIR__ . '/ask_changes_modal.php'; ?>
</body>
</html>