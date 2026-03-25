// Main JavaScript file for Inventory Management System

document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    initializeFormValidation();
});

function initializeEventListeners() {
    // Add event listeners for dynamic elements
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });

    // Tab switching
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            document.querySelectorAll('.tab.active').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
            let isValid = true;

            inputs.forEach(input => {
                if (input.value.trim() === '') {
                    input.style.borderColor = 'red';
                    isValid = false;
                } else {
                    input.style.borderColor = '';
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    });
}

function showMessage(message, type = 'info') {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message message-${type}`;
    messageDiv.textContent = message;
    document.body.insertBefore(messageDiv, document.body.firstChild);

    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}