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
    const money = (paise) => `${paise < 0n ? '-' : ''}₹${(paise < 0n ? -paise : paise) / 100n}.${((paise < 0n ? -paise : paise) % 100n).toString().padStart(2, '0')}`;
    const transactionType = document.getElementById('tax_type');
    const companySelect = document.getElementById('company_id');
    const updateTaxType = () => {
        const customerGstin = companySelect.selectedOptions[0]?.dataset.gstin || '';
        const sellerGstin = invoiceForm.dataset.sellerGstin || '';
        if (/^\d{2}/.test(customerGstin) && /^\d{2}/.test(sellerGstin)) {
            transactionType.value = sellerGstin.slice(0, 2) === customerGstin.slice(0, 2) ? 'intra' : 'inter';
        }
    };
    const preview = () => {
        let subtotal = 0n;
        items.querySelectorAll('.invoice-item').forEach((row) => {
            const quantity = parseMinor(row.querySelector('[name$="[quantity]"]').value, 3);
            const rate = parseMinor(row.querySelector('[name$="[rate]"]').value, 2);
            const lineTotal = (quantity * rate + 500n) / 1000n;
            subtotal += lineTotal;
            row.querySelector('.item-amount').textContent = money(lineTotal);
        });
        const cgstRate = parseMinor(document.getElementById('cgst_rate').value, 2);
        const sgstRate = parseMinor(document.getElementById('sgst_rate').value, 2);
        const cgst = transactionType.value === 'intra' ? (subtotal * cgstRate + 5000n) / 10000n : 0n;
        const sgst = transactionType.value === 'intra' ? (subtotal * sgstRate + 5000n) / 10000n : 0n;
        const igst = transactionType.value === 'inter' ? (subtotal * (cgstRate + sgstRate) + 5000n) / 10000n : 0n;
        const beforeRoundOff = subtotal + cgst + sgst + igst;
        const total = ((beforeRoundOff + 50n) / 100n) * 100n;
        document.getElementById('preview-subtotal').textContent = money(subtotal);
        document.getElementById('preview-cgst').textContent = money(cgst);
        document.getElementById('preview-sgst').textContent = money(sgst);
        document.getElementById('preview-igst').textContent = money(igst);
        document.getElementById('preview-round-off').textContent = money(total - beforeRoundOff);
        document.getElementById('preview-total').textContent = money(total);
    };
    const addRow = () => {
        const row = document.createElement('div');
        row.className = 'invoice-item';
        row.innerHTML = `<label>Weigh Bridge S.No.<input name="items[${nextItem}][weigh_bridge_serial]" maxlength="50"></label>
            <label>Date<input name="items[${nextItem}][item_date]" type="date" max="${document.getElementById('invoice_date').max}" required></label>
            <label>Description<input name="items[${nextItem}][description]" maxlength="255" required></label>
            <label>Weight (kg)<input name="items[${nextItem}][quantity]" type="number" min="0.001" step="0.001" value="1" required></label>
            <label>Rate (₹/kg)<input name="items[${nextItem}][rate]" type="number" min="0" step="0.01" value="0.00" required></label>
            <label>Amount (₹)<output class="item-amount">₹0.00</output></label>
            <button class="button secondary remove-item" type="button" aria-label="Remove item">Remove</button><small class="field-error item-error"></small>`;
        row.querySelector('[name$="[item_date]"]').value = document.getElementById('invoice_date').value;
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
    companySelect.addEventListener('change', () => { updateTaxType(); preview(); });
    transactionType.addEventListener('change', preview);
    invoiceForm.addEventListener('submit', (event) => {
        if (items.querySelectorAll('.invoice-item').length === 0) {
            event.preventDefault();
            window.alert('Add at least one invoice item.');
        }
    });
    updateTaxType();
    preview();
}
