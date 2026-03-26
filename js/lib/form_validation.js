/**
 * form_validation.js
 * Client-side form validation helpers for SBC Inventory.
 *
 * Usage:
 *   // Mark a field as invalid and show an error message
 *   FormValidation.setInvalid(inputEl, 'This field is required.');
 *
 *   // Clear validation state on a field
 *   FormValidation.clearInvalid(inputEl);
 *
 *   // Validate all required fields in a form; returns true if all valid
 *   const ok = FormValidation.validateRequired(formEl);
 */

const FormValidation = (() => {

    /**
     * Mark a form control as invalid and display an error message beneath it.
     *
     * @param {HTMLElement} field   - The input/select/textarea element.
     * @param {string}      message - Error message to display.
     * @returns {void}
     */
    function setInvalid(field, message) {
        const group = field.closest('.form-group');
        if (!group) return;

        field.classList.add('is-invalid');
        group.classList.add('has-error');

        // Reuse an existing feedback element or create one
        let feedback = group.querySelector('.form-invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'form-invalid-feedback';
            field.insertAdjacentElement('afterend', feedback);
        }
        feedback.textContent = message;
    }

    /**
     * Remove invalid state from a form control.
     *
     * @param {HTMLElement} field - The input/select/textarea element.
     * @returns {void}
     */
    function clearInvalid(field) {
        const group = field.closest('.form-group');
        if (!group) return;

        field.classList.remove('is-invalid');
        group.classList.remove('has-error');

        const feedback = group.querySelector('.form-invalid-feedback');
        if (feedback) feedback.textContent = '';
    }

    /**
     * Clear all validation errors within a form.
     *
     * @param {HTMLFormElement} form
     * @returns {void}
     */
    function clearAll(form) {
        form.querySelectorAll('.is-invalid').forEach(el => clearInvalid(el));
    }

    /**
     * Validate all fields inside a form that carry the [required] attribute.
     * Marks empty fields invalid and focuses the first one found.
     *
     * @param {HTMLFormElement} form
     * @returns {boolean} true when every required field has a value.
     */
    function validateRequired(form) {
        let firstInvalid = null;

        form.querySelectorAll('[required]').forEach(field => {
            const value = field.value.trim();
            if (!value) {
                setInvalid(field, 'This field is required.');
                if (!firstInvalid) firstInvalid = field;
            } else {
                clearInvalid(field);
            }
        });

        if (firstInvalid) {
            firstInvalid.focus();
            return false;
        }
        return true;
    }

    // Public API
    return { setInvalid, clearInvalid, clearAll, validateRequired };
})();