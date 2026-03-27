// Client selector: switching client reloads the page.
// User selector: switching user redirects to the home page.
document.addEventListener('DOMContentLoaded', function() {

    // Client selector
    var clientSelect = document.getElementById('client-select');
    if (clientSelect) {
        clientSelect.addEventListener('change', function() {
            var clientId = this.value;
            var endpoint = this.dataset.setUserUrl;
            if (!endpoint) { return; }

            var formData = new FormData();
            formData.append('client_id', clientId);

            fetch(endpoint, { method: 'POST', body: formData })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        console.error('Client change failed:', data.error);
                    }
                })
                .catch(function(err) {
                    console.error('Client change request failed:', err);
                });
        });
    }

    // User selector: redirect to home page when user changes
    var userSelect = document.getElementById('user-select');
    if (userSelect) {
        userSelect.addEventListener('change', function() {
            var userId   = this.value;
            var endpoint = this.dataset.setUserUrl;
            var homeUrl  = this.dataset.homeUrl;
            if (!endpoint) { return; }

            var formData = new FormData();
            formData.append('user_id', userId);

            fetch(endpoint, { method: 'POST', body: formData })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        window.location.href = data.redirect || homeUrl || '/';
                    } else {
                        console.error('User change failed:', data.error);
                    }
                })
                .catch(function(err) {
                    console.error('User change request failed:', err);
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
