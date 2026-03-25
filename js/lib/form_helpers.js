// Form Helper Functions
function validateForm(formId) {
    const form = document.getElementById(formId);
    return form ? form.checkValidity() : false;
}

function resetForm(formId) {
    const form = document.getElementById(formId);
    if (form) form.reset();
}
