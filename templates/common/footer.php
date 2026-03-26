<?php
/**
 * Common footer template.
 * Included at the bottom of every page.
 * Expects $brand (array) and $settings (array) to be set before inclusion.
 *
 * $brand['show_ask_for_changes'] (bool) - whether to show the "Ask for Changes" link
 */
$appVersion   = $settings['app_version']  ?? '0.1.0';
$contactEmail = $settings['contact_email'] ?? 'info@securitybuildingcontrols.com';
$year         = date('Y');
?>
<footer class="site-footer">
    <div class="footer-inner">
        <span class="footer-copy">&copy; <?= $year ?> Security Building Controls. All rights reserved.</span>
        <span class="footer-version">v<?= htmlspecialchars($appVersion) ?></span>
        <nav class="footer-links">
            <a href="<?= BASE_URL ?>/compliance.php">Compliance</a>
            <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a>
            <?php if (!empty($brand['show_ask_for_changes'])): ?>
                <a href="#" id="ask-for-changes-link" class="footer-ask-link">Ask for Changes</a>
            <?php endif; ?>
        </nav>
    </div>
</footer>

<?php if (!empty($brand['show_ask_for_changes'])): ?>
<!-- Ask for Changes modal -->
<div id="ask-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="ask-modal-title" hidden>
    <div class="modal-dialog">
        <h2 id="ask-modal-title">Ask for Changes</h2>
        <form id="ask-for-changes-form" novalidate>
            <div class="form-group">
                <label for="afc-name">Your Name <span class="required">*</span></label>
                <input type="text" id="afc-name" name="name" required maxlength="120">
            </div>
            <div class="form-group">
                <label for="afc-email">Your Email <span class="required">*</span></label>
                <input type="email" id="afc-email" name="email" required maxlength="254">
            </div>
            <div class="form-group">
                <label for="afc-category">Category <span class="required">*</span></label>
                <select id="afc-category" name="category" required>
                    <option value="">-- Select --</option>
                    <option value="feature_request">Feature Request</option>
                    <option value="bug_report">Bug Report</option>
                    <option value="feedback">Feedback</option>
                </select>
            </div>
            <div class="form-group">
                <label for="afc-subject">Subject <span class="required">*</span></label>
                <input type="text" id="afc-subject" name="subject" required maxlength="200">
            </div>
            <div class="form-group">
                <label for="afc-message">Message <span class="required">*</span></label>
                <textarea id="afc-message" name="message" rows="6" required maxlength="4000"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Send</button>
                <button type="button" id="ask-modal-cancel" class="btn btn-secondary">Cancel</button>
            </div>
            <div id="afc-feedback" class="form-feedback" hidden></div>
        </form>
    </div>
</div>
<script src="<?= BASE_URL ?>/js/pages/ask_for_changes.js" defer></script>
<?php endif; ?>

</body>
</html>
