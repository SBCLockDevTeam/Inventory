// Client selector: switching client reloads the page.
// User selector: switching user redirects to the home page.
// Ask for Changes modal: collects feedback and submits via AJAX.
// Hamburger menu: toggles the mobile navigation menu.
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

    // ---------------------------------------------------------------
    // Ask for Changes modal
    // ---------------------------------------------------------------
    var askLink    = document.getElementById('ask-changes');
    var modal      = document.getElementById('ask-changes-modal');
    var cancelBtn  = document.getElementById('ask-changes-cancel');
    var closeSuccBtn = document.getElementById('ask-changes-close-success');
    var form       = document.getElementById('ask-changes-form');
    var errorDiv   = document.getElementById('ask-changes-error-div');
    var successDiv = document.getElementById('ask-changes-success');
    var formWrap   = document.getElementById('ask-changes-form-wrap');
    var submitBtn  = document.getElementById('ask-submit-btn');

    function openAskModal(e) {
        e.preventDefault();
        if (!modal) { return; }
        if (form)       { form.reset(); }
        if (errorDiv)   { errorDiv.style.display = 'none'; errorDiv.textContent = ''; }
        if (successDiv) { successDiv.style.display = 'none'; }
        if (formWrap)   { formWrap.style.display = 'block'; }
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeAskModal() {
        if (!modal) { return; }
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
    }

    if (askLink)    { askLink.addEventListener('click', openAskModal); }
    if (cancelBtn)  { cancelBtn.addEventListener('click', closeAskModal); }
    if (closeSuccBtn) { closeSuccBtn.addEventListener('click', closeAskModal); }

    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) { closeAskModal(); }
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) { closeAskModal(); }
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Sending…'; }
            if (errorDiv)  { errorDiv.style.display = 'none'; errorDiv.textContent = ''; }

            var basePath = (typeof BASE_PATH !== 'undefined') ? BASE_PATH : (document.body.dataset.basePath || '');
            var formData = new FormData(form);

            fetch(basePath + '/api/ask_changes.php', { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        if (formWrap)   { formWrap.style.display = 'none'; }
                        if (successDiv) { successDiv.style.display = 'block'; }
                    } else {
                        if (errorDiv) {
                            errorDiv.textContent = data.error || 'Submission failed.';
                            errorDiv.style.display = 'block';
                        }
                    }
                })
                .catch(function(err) {
                    if (errorDiv) {
                        errorDiv.textContent = 'Request failed: ' + err;
                        errorDiv.style.display = 'block';
                    }
                })
                .finally(function() {
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Send'; }
                });
        });
    }

    // ---------------------------------------------------------------
    // Hamburger / mobile menu toggle
    // ---------------------------------------------------------------
    var hamburger = document.getElementById('menu-hamburger');
    var menuList  = document.querySelector('.menu-list');

    if (hamburger && menuList) {
        hamburger.addEventListener('click', function() {
            var expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', String(!expanded));
            menuList.classList.toggle('menu-open');
        });
    }

});

