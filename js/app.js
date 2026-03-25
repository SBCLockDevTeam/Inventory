/**
 * Main Application JavaScript
 * Global functions and event handlers
 */

// Brand selector change handler
function changeBrand(brandName) {
    // Send AJAX request to set session brand
    fetch('<?php echo $config['root_url']; ?>api/set_brand.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'brand=' + encodeURIComponent(brandName)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showError('Failed to change brand');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred');
    });
}

// Feedback form handlers
function openFeedbackForm(event) {
    event.preventDefault();
    const modal = document.getElementById('feedback-modal');
    modal.classList.remove('hidden');
    modal.classList.add('show');
}

function closeFeedbackForm() {
    const modal = document.getElementById('feedback-modal');
    modal.classList.add('hidden');
    modal.classList.remove('show');
}

// Close modal when clicking outside content
window.onclick = function(event) {
    const modal = document.getElementById('feedback-modal');
    if (event.target === modal) {
        closeFeedbackForm();
    }
}

// Form submission handler
document.addEventListener('DOMContentLoaded', function() {
    const feedbackForm = document.getElementById('feedback-form');
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitFeedback(this);
        });
    }
});

function submitFeedback(form) {
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Thank you! Your feedback has been submitted.');
            closeFeedbackForm();
            form.reset();
        } else {
            showError(data.message || 'Failed to submit feedback');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred while submitting feedback');
    });
}

// Utility function to show error messages
function showError(message) {
    console.error(message);
    alert('Error: ' + message);
}

// Utility function to show success messages
function showSuccess(message) {
    console.log(message);
    alert(message);
}