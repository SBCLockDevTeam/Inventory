<?php
/**
 * Error Division Template
 * Displays error, warning, and notice messages with color coding
 * Messages are stored in session and displayed once, then cleared
 */

// Get error messages from session
$errors = $_SESSION['errors'] ?? [];$warnings = $_SESSION['warnings'] ?? [];$notices = $_SESSION['notices'] ?? [];

// Check if there are any messages to display
$hasMessages = !empty($errors) || !empty($warnings) || !empty($notices);

// Clear messages from session after retrieving them
unset($_SESSION['errors'], $_SESSION['warnings'], $_SESSION['notices']);
?>

<div id="error-division" class="error-division <?php echo $hasMessages ? '' : 'hidden'; ?>" role="alert" aria-live="polite">
    <?php if (!empty($errors)): ?>
        <div class="message-container error-container">
            <div class="message-header">
                <span class="message-icon">❌</span>
                <span class="message-type">Error</span>
                <button class="message-dismiss" onclick="dismissMessage(this)" aria-label="Dismiss error">×</button>
            </div>
            <div class="message-body">
                <?php if (count($errors) === 1): ?>
                    <p><?php echo htmlspecialchars($errors[0]); ?></p>
                <?php else: ?>
                    <ul class="message-list">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($warnings)): ?>
        <div class="message-container warning-container">
            <div class="message-header">
                <span class="message-icon">⚠️</span>
                <span class="message-type">Warning</span>
                <button class="message-dismiss" onclick="dismissMessage(this)" aria-label="Dismiss warning">×</button>
            </div>
            <div class="message-body">
                <?php if (count($warnings) === 1): ?>
                    <p><?php echo htmlspecialchars($warnings[0]); ?></p>
                <?php else: ?>
                    <ul class="message-list">
                        <?php foreach ($warnings as $warning): ?>
                            <li><?php echo htmlspecialchars($warning); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($notices)): ?>
        <div class="message-container notice-container">
            <div class="message-header">
                <span class="message-icon">✓</span>
                <span class="message-type">Success</span>
                <button class="message-dismiss" onclick="dismissMessage(this)" aria-label="Dismiss notice">×</button>
            </div>
            <div class="message-body">
                <?php if (count($notices) === 1): ?>
                    <p><?php echo htmlspecialchars($notices[0]); ?></p>
                <?php else: ?>
                    <ul class="message-list">
                        <?php foreach ($notices as $notice): ?>
                            <li><?php echo htmlspecialchars($notice); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function dismissMessage(button) {
    const container = button.closest('.message-container');
    const division = document.getElementById('error-division');
    container.style.opacity = '0';
    container.style.transform = 'translateY(-10px)';
    setTimeout(() => {
        container.remove();
        if (division.querySelectorAll('.message-container').length === 0) {
            division.classList.add('hidden');
        }
    }, 300);
}

function displayMessage(message, type = 'notice') {
    const division = document.getElementById('error-division');
    division.classList.remove('hidden');
    const icons = { error: '❌', warning: '⚠️', notice: '✓' };
    const labels = { error: 'Error', warning: 'Warning', notice: 'Success' };
    const container = document.createElement('div');
    container.className = `message-container ${type}-container`;
    container.innerHTML = `
        <div class="message-header">
            <span class="message-icon">${icons[type]}</span>
            <span class="message-type">${labels[type]}</span>
            <button class="message-dismiss" onclick="dismissMessage(this)" aria-label="Dismiss ${type}">×</button>
        </div>
        <div class="message-body"><p>${escapeHtml(message)}</p></div>
    `;
    division.appendChild(container);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() {
    const notices = document.querySelectorAll('.notice-container');
    notices.forEach(notice => {
        setTimeout(() => {
            const dismissBtn = notice.querySelector('.message-dismiss');
            if (dismissBtn) dismissMessage(dismissBtn);
        }, 5000);
    });
});
</script>
