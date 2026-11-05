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

const invoiceForm = document.getElementById('invoice-form');
if (invoiceForm) {
    const items = document.getElementById('invoice-items');
    let nextItem = items.querySelectorAll('.invoice-item').length;
    const parseMinor = (value, places) => {
        const clean = value.trim();
        if (!/^(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$/.test(clean)) return 0n;
        const parts = clean.split('.');
        return BigInt(parts[0]) * (10n ** BigInt(places))
            + BigInt((parts[1] || '').padEnd(places, '0').slice(0, places) || '0');
    };
    const money = (paise) => `₹${(paise / 100n).toString()}.${(paise % 100n).toString().padStart(2, '0')}`;
    const preview = () => {
        let subtotal = 0n;
        items.querySelectorAll('.invoice-item').forEach((row) => {
            const fields = row.querySelectorAll('input');
            const quantity = parseMinor(fields[1].value, 3);
            const rate = parseMinor(fields[2].value, 2);
            subtotal += (quantity * rate + 500n) / 1000n;
        });
        const cgst = (subtotal * parseMinor(document.getElementById('cgst_rate').value, 2) + 5000n) / 10000n;
        const sgst = (subtotal * parseMinor(document.getElementById('sgst_rate').value, 2) + 5000n) / 10000n;
        document.getElementById('preview-subtotal').textContent = money(subtotal);
        document.getElementById('preview-cgst').textContent = money(cgst);
        document.getElementById('preview-sgst').textContent = money(sgst);
        document.getElementById('preview-total').textContent = money(subtotal + cgst + sgst);
    };
    const addRow = () => {
        const row = document.createElement('div');
        row.className = 'invoice-item';
        row.innerHTML = `<label>Description<input name="items[${nextItem}][description]" maxlength="255" required></label>
            <label>Quantity<input name="items[${nextItem}][quantity]" type="number" min="0.001" step="0.001" value="1" required></label>
            <label>Rate (₹)<input name="items[${nextItem}][rate]" type="number" min="0" step="0.01" value="0.00" required></label>
            <button class="button secondary remove-item" type="button" aria-label="Remove item">Remove</button><small class="field-error item-error"></small>`;
        nextItem += 1;
        items.append(row);
    };
    document.getElementById('add-item').addEventListener('click', addRow);
    items.addEventListener('click', (event) => {
        if (event.target.closest('.remove-item') && items.querySelectorAll('.invoice-item').length > 1) {
            event.target.closest('.invoice-item').remove();
            preview();
        }
    });
    invoiceForm.addEventListener('input', preview);
    invoiceForm.addEventListener('submit', (event) => {
        if (items.querySelectorAll('.invoice-item').length === 0) {
            event.preventDefault();
            window.alert('Add at least one invoice item.');
        }
    });
    preview();
}
