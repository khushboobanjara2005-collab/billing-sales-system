<?php
use App\Core\View;
$profileValue = static fn (string $key): string => View::escape($profile[$key] ?? '');
?>
<section class="page-heading">
    <div><p class="eyebrow">INVOICE SETTINGS</p><h1>Seller Profile</h1>
        <p class="muted">These seller, bank, and terms details appear on invoices.</p>
    </div>
    <a class="button secondary" href="<?= View::escape($baseUrl) ?>/index.php?r=invoices/index">Back to invoices</a>
</section>
<section class="panel form-panel">
    <?php if (!empty($message)): ?><div class="flash <?= View::escape($messageType) ?>" role="alert"><?= View::escape($message) ?></div><?php endif; ?>
    <div class="flash error" role="note">The prefilled address, GSTIN, and bank information are demo values. Replace them before issuing real invoices.</div>
    <?php if (!empty($errors)): ?><div class="flash error" role="alert">Please correct the highlighted fields.</div><?php endif; ?>
    <form method="post" action="<?= View::escape($baseUrl) ?>/index.php?r=seller-profile/update">
        <input type="hidden" name="_csrf" value="<?= View::escape($csrf) ?>">
        <div class="form-grid">
            <?php foreach ([
                'seller_name' => ['Seller name', 150], 'business_description' => ['Business description', 255],
                'address' => ['Business address', 500], 'phone' => ['Phone', 30], 'gstin' => ['Seller GSTIN', 15],
                'bank_name' => ['Bank name', 100], 'branch' => ['Bank branch', 100],
                'account_number' => ['Account number', 34], 'ifsc_code' => ['IFSC code', 11],
            ] as $key => [$label, $length]): ?>
                <div class="field"><label for="<?= View::escape($key) ?>"><?= View::escape($label) ?></label>
                    <input id="<?= View::escape($key) ?>" name="<?= View::escape($key) ?>" maxlength="<?= (int) $length ?>" value="<?= $profileValue($key) ?>">
                    <small class="field-error"><?= View::escape($errors[$key] ?? '') ?></small>
                </div>
            <?php endforeach; ?>
            <div class="field full-width"><label for="terms">Terms &amp; Conditions</label>
                <textarea id="terms" name="terms" rows="6" maxlength="4000"><?= $profileValue('terms') ?></textarea>
                <small class="field-error"><?= View::escape($errors['terms'] ?? '') ?></small>
            </div>
        </div>
        <div class="form-actions"><button class="button primary" type="submit">Save seller profile</button></div>
    </form>
</section>
