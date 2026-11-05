<?php use App\Core\View; ?>
<section class="page-heading">
    <div><p class="eyebrow">CASH FLOW</p><h1>Record payment</h1></div>
    <a class="button secondary" href="<?= View::escape($baseUrl) ?>/index.php?r=payments/index">Back to payments</a>
</section>
<section class="panel form-panel">
    <?php if (!empty($message)): ?><div class="flash error" role="alert"><?= View::escape($message) ?></div><?php endif; ?>
    <form method="post" action="<?= View::escape($baseUrl) ?>/index.php?r=payments/store">
        <input type="hidden" name="_csrf" value="<?= View::escape($csrf) ?>">
        <div class="form-grid">
            <div class="field"><label for="invoice_id">Invoice</label>
                <select id="invoice_id" name="invoice_id" required>
                    <option value="">Choose invoice</option>
                    <?php foreach ($invoices as $invoice): ?>
                        <?php if (preg_match('/[1-9]/', (string) $invoice['pending_amount'])): ?>
                            <option value="<?= (int) $invoice['invoice_id'] ?>" <?= $invoiceId === (int) $invoice['invoice_id'] ? 'selected' : '' ?>>
                                <?= View::escape($invoice['invoice_number'] . ' · ' . $invoice['company_name'] . ' · pending ₹' . $invoice['pending_amount']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <small class="field-error"><?= View::escape($errors['invoice_id'] ?? '') ?></small>
            </div>
            <div class="field"><label for="payment_date">Payment date</label>
                <input id="payment_date" name="payment_date" type="date" max="<?= date('Y-m-d') ?>" required value="<?= View::escape($values['payment_date'] ?? date('Y-m-d')) ?>">
                <small class="field-error"><?= View::escape($errors['payment_date'] ?? '') ?></small>
            </div>
            <div class="field"><label for="amount">Amount (₹)</label>
                <input id="amount" name="amount" type="number" min="0.01" max="9999999999.99" step="0.01" required value="<?= View::escape($values['amount'] ?? '') ?>">
                <small class="field-error"><?= View::escape($errors['amount'] ?? '') ?></small>
            </div>
            <div class="field"><label for="payment_method">Method</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="">Choose method</option>
                    <?php foreach (['Cash', 'UPI', 'Bank Transfer', 'Cheque'] as $method): ?>
                        <option <?= ($values['payment_method'] ?? '') === $method ? 'selected' : '' ?>><?= $method ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="field-error"><?= View::escape($errors['payment_method'] ?? '') ?></small>
            </div>
        </div>
        <div class="form-actions"><button class="button primary" type="submit">Record payment</button>
            <a class="button secondary" href="<?= View::escape($baseUrl) ?>/index.php?r=payments/index">Cancel</a></div>
    </form>
</section>
