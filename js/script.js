// Brand selector: persist selection via AJAX and reload the page to apply the brand theme.
// Brand affects only header, footer, and page styling — not inventory contents.
document.addEventListener('DOMContentLoaded', function() {
    var brandSelect = document.getElementById('brand-select');
    if (brandSelect) {
        brandSelect.addEventListener('change', function() {
            var brandId  = this.value;
            var endpoint = this.dataset.setBrandUrl;
            if (!endpoint) { return; }

            var formData = new FormData();
            formData.append('brand_id', brandId);

            fetch(endpoint, { method: 'POST', body: formData })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        // Reload so the new brand theme is applied to header/footer/styles
                        window.location.reload();
                    } else {
                        console.error('Brand change failed:', data.error);
                    }
                })
                .catch(function(err) {
                    console.error('Brand change request failed:', err);
                });
        });
    }

    // Error message handling
    window.showError = function(message, type) {
        type = type || 'error';
        var errorDiv = document.getElementById('error-messages');
        if (errorDiv) {
            errorDiv.innerHTML += '<div class="' + type + '">' + message + '</div>';
            errorDiv.style.display = 'block';
        }
    };

    window.clearErrors = function() {
        var errorDiv = document.getElementById('error-messages');
        if (errorDiv) {
            errorDiv.innerHTML = '';
            errorDiv.style.display = 'none';
        }
    };

    // Ask for changes modal (placeholder)
    var askChanges = document.getElementById('ask-changes');
    if (askChanges) {
        askChanges.addEventListener('click', function(e) {
            e.preventDefault();
            // TODO: Show modal for feedback
            alert('Feedback form coming soon!');
        });
    }
});
