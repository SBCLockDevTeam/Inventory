<?php require_once __DIR__ . '/../../config/settings.php'; ?>
<!-- Ask for Changes Modal -->
<div id="ask-changes-modal" class="modal-overlay" aria-hidden="true" role="dialog" aria-labelledby="ask-changes-title">
    <div class="modal-dialog">
        <h2 id="ask-changes-title">Ask for Changes</h2>

        <div id="ask-changes-success" style="display:none;">
            <p class="success-message">✓ Your message has been sent. Thank you!</p>
            <button type="button" class="btn btn-secondary" id="ask-changes-close-success">Close</button>
        </div>

        <div id="ask-changes-form-wrap">
            <div id="ask-changes-error-div" class="error-banner" style="display:none;"></div>
            <form id="ask-changes-form" novalidate>
                <div class="form-group">
                    <label for="ask-name">Your Name <span class="required">*</span></label>
                    <input type="text" id="ask-name" name="submitter_name" required>
                </div>
                <div class="form-group">
                    <label for="ask-email">Your Email <span class="required">*</span></label>
                    <input type="email" id="ask-email" name="submitter_email" required>
                </div>
                <div class="form-group">
                    <label for="ask-category">Category <span class="required">*</span></label>
                    <select id="ask-category" name="category" required>
                        <option value="feature_request">Feature Request</option>
                        <option value="bug_report">Bug Report</option>
                        <option value="feedback">General Feedback</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ask-subject">Subject <span class="required">*</span></label>
                    <input type="text" id="ask-subject" name="subject" required>
                </div>
                <div class="form-group">
                    <label for="ask-message">Message <span class="required">*</span></label>
                    <textarea id="ask-message" name="message" rows="5" required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="ask-submit-btn">Send</button>
                    <button type="button" class="btn btn-secondary" id="ask-changes-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
