<?php
use App\Core\View;
$rows = $items === [] ? [['weigh_bridge_serial' => '', 'item_date' => '', 'description' => '', 'quantity' => '1', 'rate' => '0.00']] : $items;
$val = static fn (string $key, mixed $default = ''): string => View::escape($invoice[$key] ?? $default);
?>
<section class="page-heading">
    <div><p class="eyebrow">BILLING</p><h1><?= View::escape($title) ?></h1></div>
    <a class="button secondary" href="<?= View::escape($baseUrl) ?>/index.php?r=invoices/index">Back to invoices</a>
</section>
<section class="panel form-panel invoice-form-panel">
    <?php if (!empty($message)): ?><div class="flash error" role="alert"><?= View::escape($message) ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="flash error" role="alert">Please review the highlighted invoice fields.</div><?php endif; ?>
    <form method="post" action="<?= View::escape($baseUrl) ?>/index.php?r=invoices/<?= $id === null ? 'store' : 'update' ?>"
          id="invoice-form" data-seller-gstin="<?= View::escape($seller['gstin'] ?? '') ?>">
        <input type="hidden" name="_csrf" value="<?= View::escape($csrf) ?>">
        <?php if ($id !== null): ?><input type="hidden" name="invoice_id" value="<?= (int) $id ?>"><?php endif; ?>
        <div class="form-grid">
            <div class="field"><label for="company_id">Company</label>
                <select id="company_id" name="company_id" required>
                    <option value="">Choose company</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= (int) $company['company_id'] ?>" data-gstin="<?= View::escape($company['gstin'] ?? '') ?>" <?= (int) ($invoice['company_id'] ?? 0) === (int) $company['company_id'] ? 'selected' : '' ?>><?= View::escape($company['company_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="field-error"><?= View::escape($errors['company_id'] ?? '') ?></small>
            </div>
            <div class="field"><label for="invoice_date">Invoice date</label>
                <input id="invoice_date" name="invoice_date" type="date" required max="<?= date('Y-m-d') ?>" value="<?= $val('invoice_date', date('Y-m-d')) ?>">
                <small class="field-error"><?= View::escape($errors['invoice_date'] ?? '') ?></small>
            </div>
            <div class="field"><label for="cgst_rate">CGST rate (%)</label>
                <input id="cgst_rate" name="cgst_rate" type="number" min="0" max="999.99" step="0.01" value="<?= $val('cgst_rate', '2.50') ?>" required>
                <small class="field-error"><?= View::escape($errors['cgst_rate'] ?? '') ?></small>
            </div>
            <div class="field"><label for="sgst_rate">SGST rate (%)</label>
                <input id="sgst_rate" name="sgst_rate" type="number" min="0" max="999.99" step="0.01" value="<?= $val('sgst_rate', '2.50') ?>" required>
                <small class="field-error"><?= View::escape($errors['sgst_rate'] ?? '') ?></small>
            </div>
            <div class="field"><label for="tax_type">GST type when GSTIN is unavailable</label>
                <select id="tax_type" name="tax_type">
                    <option value="intra" <?= ($invoice['tax_type'] ?? 'intra') === 'intra' ? 'selected' : '' ?>>Intra-state (CGST + SGST)</option>
                    <option value="inter" <?= ($invoice['tax_type'] ?? '') === 'inter' ? 'selected' : '' ?>>Inter-state (IGST)</option>
                </select>
                <small class="field-error"><?= View::escape($errors['tax_type'] ?? '') ?></small>
            </div>
        </div>
        <h2 class="section-title">Line items</h2>
        <div id="invoice-items" class="invoice-items">
            <?php foreach ($rows as $index => $item): ?>
                <div class="invoice-item">
                    <label>Weigh Bridge S.No.<input name="items[<?= (int) $index ?>][weigh_bridge_serial]" maxlength="50" value="<?= View::escape($item['weigh_bridge_serial'] ?? '') ?>"></label>
                    <label>Date<input name="items[<?= (int) $index ?>][item_date]" type="date" max="<?= date('Y-m-d') ?>" value="<?= View::escape(!empty($item['item_date']) ? $item['item_date'] : ($invoice['invoice_date'] ?? date('Y-m-d'))) ?>" required></label>
                    <label>Description<input name="items[<?= (int) $index ?>][description]" maxlength="255" required value="<?= View::escape($item['description'] ?? '') ?>"></label>
                    <label>Weight (kg)<input name="items[<?= (int) $index ?>][quantity]" type="number" min="0.001" step="0.001" required value="<?= View::escape($item['quantity'] ?? '') ?>"></label>
                    <label>Rate (₹/kg)<input name="items[<?= (int) $index ?>][rate]" type="number" min="0" step="0.01" required value="<?= View::escape($item['rate'] ?? '') ?>"></label>
                    <label>Amount (₹)<output class="item-amount">₹0.00</output></label>
                    <button class="button secondary remove-item" type="button" aria-label="Remove item">Remove</button>
                    <small class="field-error item-error"><?= View::escape($errors['items.' . $index . '.weigh_bridge_serial'] ?? $errors['items.' . $index . '.item_date'] ?? $errors['items.' . $index . '.description'] ?? $errors['items.' . $index . '.quantity'] ?? $errors['items.' . $index . '.rate'] ?? '') ?></small>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="button secondary" type="button" id="add-item">Add line item</button>
        <div class="invoice-preview" aria-live="polite">
            <span>Subtotal <strong id="preview-subtotal">₹0.00</strong></span>
            <span>CGST <strong id="preview-cgst">₹0.00</strong></span>
            <span>SGST <strong id="preview-sgst">₹0.00</strong></span>
            <span>IGST <strong id="preview-igst">₹0.00</strong></span>
            <span>Round Off <strong id="preview-round-off">₹0.00</strong></span>
            <span class="preview-total">Total <strong id="preview-total">₹0.00</strong></span>
        </div>
        <small class="field-error"><?= View::escape($errors['items'] ?? '') ?></small>
        <div class="form-actions"><button class="button primary" type="submit"><?= $id === null ? 'Create invoice' : 'Save changes' ?></button>
            <a class="button secondary" href="<?= View::escape($baseUrl) ?>/index.php?r=invoices/index">Cancel</a></div>
    </form>
</section>
