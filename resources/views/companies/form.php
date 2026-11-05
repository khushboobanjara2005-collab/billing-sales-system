<?php
use App\Core\View;

$fields = ['company_name' => 'Company name', 'gstin' => 'GSTIN', 'phone' => 'Phone', 'email' => 'Email', 'address' => 'Address'];
$value = static fn (string $field): string => View::escape($company[$field] ?? '');
$error = static fn (string $field): string => View::escape($errors[$field] ?? '');
?>
<section class="page-heading">
    <div><p class="eyebrow">COMPANIES</p><h1><?= View::escape($title) ?></h1></div>
    <a class="button secondary" href="<?= View::escape($baseUrl) ?>/index.php?r=companies/index">Back to companies</a>
</section>
<section class="panel form-panel">
    <?php if (!empty($message)): ?><div class="flash <?= View::escape($messageType) ?>" role="alert"><?= View::escape($message) ?></div><?php endif; ?>
    <form method="post" action="<?= View::escape($baseUrl) ?>/index.php?r=<?= View::escape($action) ?>" data-validate>
        <input type="hidden" name="_csrf" value="<?= View::escape($csrf) ?>">
        <?php if ($id !== null): ?><input type="hidden" name="company_id" value="<?= (int) $id ?>"><?php endif; ?>
        <div class="form-grid">
            <div class="field">
                <label for="company_name">Company name <span aria-hidden="true">*</span></label>
                <input id="company_name" name="company_name" required minlength="2" maxlength="150" value="<?= $value('company_name') ?>">
                <small class="field-error" data-error-for="company_name"><?= $error('company_name') ?></small>
            </div>
            <div class="field">
                <label for="gstin">GSTIN <span class="muted">(optional)</span></label>
                <input id="gstin" name="gstin" maxlength="15" pattern="[0-9]{2}[A-Za-z]{5}[0-9]{4}[A-Za-z][1-9A-Za-z]Z[0-9A-Za-z]" value="<?= $value('gstin') ?>">
                <small class="field-error" data-error-for="gstin"><?= $error('gstin') ?></small>
            </div>
            <div class="field">
                <label for="phone">Phone <span class="muted">(optional)</span></label>
                <input id="phone" name="phone" inputmode="numeric" maxlength="10" pattern="[0-9]{10}" value="<?= $value('phone') ?>">
                <small class="field-error" data-error-for="phone"><?= $error('phone') ?></small>
            </div>
            <div class="field">
                <label for="email">Email <span class="muted">(optional)</span></label>
                <input id="email" name="email" type="email" maxlength="254" value="<?= $value('email') ?>">
                <small class="field-error" data-error-for="email"><?= $error('email') ?></small>
            </div>
            <div class="field full-width">
                <label for="address">Address <span class="muted">(optional)</span></label>
                <textarea id="address" name="address" maxlength="500" rows="4"><?= $value('address') ?></textarea>
                <small class="field-error" data-error-for="address"><?= $error('address') ?></small>
            </div>
        </div>
        <div class="form-actions">
            <button class="button primary" type="submit"><?= $id === null ? 'Create company' : 'Save changes' ?></button>
            <a class="button secondary" href="<?= View::escape($baseUrl) ?>/index.php?r=companies/index">Cancel</a>
        </div>
    </form>
</section>
