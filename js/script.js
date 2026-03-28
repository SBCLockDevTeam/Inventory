// Avatar dropdown toggle, Ask for Changes modal, and hamburger menu.
document.addEventListener('DOMContentLoaded', function() {

    // ---------------------------------------------------------------
    // User avatar dropdown
    // ---------------------------------------------------------------
    var avatarToggle   = document.getElementById('user-avatar-toggle');
    var avatarDropdown = document.getElementById('user-avatar-dropdown');

    if (avatarToggle && avatarDropdown) {
        avatarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            var open = avatarDropdown.classList.toggle('open');
            avatarToggle.setAttribute('aria-expanded', String(open));
            avatarDropdown.setAttribute('aria-hidden', String(!open));
        });

        document.addEventListener('click', function(e) {
            if (!avatarDropdown.contains(e.target) && e.target !== avatarToggle) {
                avatarDropdown.classList.remove('open');
                avatarToggle.setAttribute('aria-expanded', 'false');
                avatarDropdown.setAttribute('aria-hidden', 'true');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && avatarDropdown.classList.contains('open')) {
                avatarDropdown.classList.remove('open');
                avatarToggle.setAttribute('aria-expanded', 'false');
                avatarDropdown.setAttribute('aria-hidden', 'true');
                avatarToggle.focus();
            }
        });
    }

    // Error message handling
    window.showError = function(message, type) {
        type = type || 'error';
        var errorDiv = document.getElementById('error-messages');
        if (errorDiv) {
            var msgDiv = document.createElement('div');
            msgDiv.className = type;
            msgDiv.textContent = message;
            errorDiv.appendChild(msgDiv);
            errorDiv.style.display = 'block';
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
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

