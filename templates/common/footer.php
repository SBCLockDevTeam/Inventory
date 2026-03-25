<?php
/**
 * Footer Template
 */

$config = include __DIR__ . '/../../config/settings.php';
$currentYear = date('Y');
?>

    <footer class="app-footer">
        <div class="footer-content">
            <div class="footer-section">
                <p class="copyright">&copy; <?php echo $currentYear; ?> Security Building Controls. All rights reserved.</p>
                <p class="version">Version <?php echo htmlspecialchars($config['app_version']); ?></p>
            </div>
            
            <div class="footer-section">
                <ul class="footer-links">
                    <li><a href="<?php echo $config['root_url']; ?>compliance.php">Compliance</a></li>
                    <li><a href="<?php echo $config['root_url']; ?>privacy.php">Privacy Policy</a></li>
                    <li><a href="<?php echo $config['root_url']; ?>contact.php">Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <a href="#" class="feedback-link" onclick="openFeedbackForm(event)">Ask for Changes</a>
            </div>
        </div>
    </footer>

    <!-- JavaScript Files -->
    <script src="<?php echo $config['root_url']; ?>js/lib/ajax_helpers.js"></script>
    <script src="<?php echo $config['root_url']; ?>js/lib/form_helpers.js"></script>
    <script src="<?php echo $config['root_url']; ?>js/app.js"></script>

    <!-- Feedback Modal -->
    <div id="feedback-modal" class="modal hidden">
        <div class="modal-content">
            <button class="close-button" onclick="closeFeedbackForm()">&times;</button>
            <h2>Send Feedback</h2>
            <form id="feedback-form" method="POST" action="<?php echo $config['root_url']; ?>api/feedback.php">
                <input type="hidden" name="user_ip" value="<?php echo htmlspecialchars($_SERVER['REMOTE_ADDR']); ?>">
                
                <label for="feedback-name">Name:</label>
                <input type="text" id="feedback-name" name="name" required>
                
                <label for="feedback-email">Email:</label>
                <input type="email" id="feedback-email" name="email" required>
                
                <label for="feedback-category">Category:</label>
                <select id="feedback-category" name="category" required>
                    <option value="">-- Select --</option>
                    <option value="feature_request">Feature Request</option>
                    <option value="bug_report">Bug Report</option>
                    <option value="feedback">General Feedback</option>
                </select>
                
                <label for="feedback-subject">Subject:</label>
                <input type="text" id="feedback-subject" name="subject" required>
                
                <label for="feedback-message">Message:</label>
                <textarea id="feedback-message" name="message" required></textarea>
                
                <button type="submit" class="btn-primary">Submit</button>
            </form>
        </div>
    </div>

    <script>
        function openFeedbackForm(e) {
            e.preventDefault();
            document.getElementById('feedback-modal').classList.remove('hidden');
            document.getElementById('feedback-modal').classList.add('show');
        }
        
        function closeFeedbackForm() {
            document.getElementById('feedback-modal').classList.add('hidden');
            document.getElementById('feedback-modal').classList.remove('show');
        }
    </script>

</body>
</html>
