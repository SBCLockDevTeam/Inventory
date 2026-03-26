/**
 * ask_for_changes.js
 * Controls the "Ask for Changes" modal in the footer.
 * Opens modal on link click, closes on cancel/backdrop click,
 * submits form via AJAX POST to /qr/api/ask_for_changes.php.
 *
 * Depends on: js/lib/ajax_helpers.js (postJSON)
 */
document.addEventListener('DOMContentLoaded', function () {
    const link      = document.getElementById('ask-for-changes-link');
    const modal     = document.getElementById('ask-modal');
    const cancelBtn = document.getElementById('ask-modal-cancel');
    const form      = document.getElementById('ask-for-changes-form');
    const feedback  = document.getElementById('afc-feedback');

    if (!link || !modal) return;

    /** Show the modal. */
    function openModal() {
        modal.hidden = false;
        modal.querySelector('input, select, textarea').focus();
    }

    /** Hide the modal and reset the form. */
    function closeModal() {
        modal.hidden = true;
        form.reset();
        feedback.hidden = true;
        feedback.textContent = '';
        feedback.className = 'form-feedback';
    }

    link.addEventListener('click', function (e) {
        e.preventDefault();
        openModal();
    });

    cancelBtn.addEventListener('click', closeModal);

    // Close when clicking the backdrop (outside the dialog box).
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    // Close on Escape key.
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) closeModal();
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const submitBtn = form.querySelector('[type="submit"]');
        submitBtn.disabled = true;

        const payload = {
            name:     form.elements['name'].value.trim(),
            email:    form.elements['email'].value.trim(),
            category: form.elements['category'].value,
            subject:  form.elements['subject'].value.trim(),
            message:  form.elements['message'].value.trim(),
        };

        try {
            const result = await postJSON('/qr/api/ask_for_changes.php', payload);

            feedback.hidden = false;
            if (result.success) {
                feedback.className = 'form-feedback form-feedback--success';
                feedback.textContent = 'Thank you! Your message has been sent.';
                form.reset();
            } else {
                feedback.className = 'form-feedback form-feedback--error';
                feedback.textContent = result.message || 'An error occurred. Please try again.';
            }
        } catch (err) {
            feedback.hidden = false;
            feedback.className = 'form-feedback form-feedback--error';
            feedback.textContent = 'A network error occurred. Please try again.';
        } finally {
            submitBtn.disabled = false;
        }
    });
});
