document.querySelectorAll('.menu-toggle').forEach((button) => {
    button.addEventListener('click', () => {
        const sidebar = document.getElementById(button.getAttribute('aria-controls'));
        const open = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', String(!open));
        sidebar.classList.toggle('open', !open);
    });
});

document.querySelectorAll('form[data-validate]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        let valid = true;
        form.querySelectorAll('input:not([type="hidden"]), textarea').forEach((field) => {
            const error = form.querySelector(`[data-error-for="${field.name}"]`);
            let message = '';
            if (field.name === 'company_name' && field.value.trim().length < 2) {
                message = 'Company name must be at least 2 characters.';
            } else if (!field.checkValidity()) {
                message = field.name === 'gstin' ? 'Enter a valid 15-character GSTIN.'
                    : field.name === 'phone' ? 'Phone must contain exactly 10 digits.'
                        : field.name === 'email' ? 'Enter a valid email address.'
                            : field.validationMessage;
            }
            if (error) error.textContent = message;
            field.setAttribute('aria-invalid', String(Boolean(message)));
            if (message) valid = false;
        });
        if (!valid) event.preventDefault();
    });
});

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
});
