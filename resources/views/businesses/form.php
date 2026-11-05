<?php
use App\Core\View;
$profileValue = static fn (string $key, string $default = ''): string => View::escape($business[$key] ?? $default);
$fields = [
    'business_name' => ['Business name', 150, true], 'tagline' => ['Tagline / description', 255, false],
    'business_type' => ['Business type', 100, false], 'owner_name' => ['Owner name', 150, false],
    'address' => ['Business address', 500, false], 'city' => ['City', 100, false],
    'state' => ['State', 100, false], 'pincode' => ['PIN code', 10, false],
    'phone' => ['Phone number', 20, false], 'email' => ['Business email', 254, false],
    'gstin' => ['GSTIN', 15, false], 'state_code' => ['State code', 2, false],
    'bank_name' => ['Bank name', 100, false], 'branch_name' => ['Branch name', 100, false],
    'account_holder_name' => ['Account holder name', 150, false],
    'account_number' => ['Account number', 34, false], 'ifsc_code' => ['IFSC code', 11, false],
    'invoice_prefix' => ['Invoice prefix', 20, true], 'next_invoice_number' => ['Next invoice number', 9, true],
    'default_gst_rate' => ['Default CGST / SGST rate (%)', 5, true],
    'jurisdiction' => ['Business jurisdiction', 150, true],
    'authorized_signatory' => ['Authorized signatory', 150, false],
];
?>
<section class="page-heading">
    <div><p class="eyebrow">BUSINESS SETTINGS</p><h1><?= View::escape($title) ?></h1>
        <p class="muted">Business, bank, and invoice settings are used only for the selected business.</p>
    </div>
</section>
<section class="panel form-panel">
    <?php if (!empty($message)): ?><div class="flash <?= View::escape($messageType ?? 'error') ?>" role="alert"><?= View::escape($message) ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="flash error" role="alert">Please correct the highlighted fields.</div><?php endif; ?>
    <form method="post" action="<?= View::escape($baseUrl) ?>/index.php?r=<?= View::escape($action) ?>">
        <input type="hidden" name="_csrf" value="<?= View::escape($csrf) ?>">
        <div class="form-grid">
            <?php foreach ($fields as $key => [$label, $length, $required]): ?>
                <?php
                $type = match ($key) {
                    'email' => 'email',
                    'phone' => 'tel',
                    'next_invoice_number', 'default_gst_rate' => 'number',
                    default => 'text',
                };
                $numericAttributes = $key === 'next_invoice_number' ? 'min="1" max="999999999" step="1"'
                    : ($key === 'default_gst_rate' ? 'min="0" max="99.99" step="0.01"' : '');
                ?>
                <div class="field">
                    <label for="<?= View::escape($key) ?>"><?= View::escape($label) ?></label>
                    <input id="<?= View::escape($key) ?>" name="<?= View::escape($key) ?>" type="<?= $type ?>"
                        maxlength="<?= (int) $length ?>" <?= $numericAttributes ?> <?= $required ? 'required' : '' ?>
                        value="<?= $profileValue($key, $key === 'invoice_prefix' ? 'INV' : ($key === 'next_invoice_number' ? '1' : ($key === 'default_gst_rate' ? '2.50' : ''))) ?>">
                    <small class="field-error"><?= View::escape($errors[$key] ?? '') ?></small>
                </div>
            <?php endforeach; ?>
            <div class="field full-width">
                <label for="terms_and_conditions">Terms and conditions</label>
                <textarea id="terms_and_conditions" name="terms_and_conditions" rows="5" maxlength="4000"><?= $profileValue('terms_and_conditions') ?></textarea>
                <small class="field-error"><?= View::escape($errors['terms_and_conditions'] ?? '') ?></small>
            </div>
        </div>
        <div class="form-actions"><button class="button primary" type="submit"><?= $id === null ? 'Create business' : 'Save business profile' ?></button></div>
    </form>
</section>
