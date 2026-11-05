<?php use App\Core\View; ?>
<section class="auth-card">
    <p class="eyebrow">NEW WORKSPACE</p>
    <h1>Create account</h1>
    <p class="muted">Your password is stored as a secure hash.</p>
    <?php if (!empty($errors)): ?><div class="flash error" role="alert">Please correct the highlighted fields.</div><?php endif; ?>
    <form method="post" action="<?= View::escape($baseUrl) ?>/index.php?r=auth/register">
        <input type="hidden" name="_csrf" value="<?= View::escape($csrf) ?>">
        <div class="field"><label for="full_name">Full name</label>
            <input id="full_name" name="full_name" autocomplete="name" maxlength="150" required value="<?= View::escape($values['full_name'] ?? '') ?>">
            <small class="field-error"><?= View::escape($errors['full_name'] ?? '') ?></small>
        </div>
        <div class="field"><label for="email">Email address</label>
            <input id="email" name="email" type="email" autocomplete="email" maxlength="254" required value="<?= View::escape($values['email'] ?? '') ?>">
            <small class="field-error"><?= View::escape($errors['email'] ?? '') ?></small>
        </div>
        <div class="field"><label for="phone">Phone number <span class="muted">(optional)</span></label>
            <input id="phone" name="phone" inputmode="numeric" maxlength="10" value="<?= View::escape($values['phone'] ?? '') ?>">
            <small class="field-error"><?= View::escape($errors['phone'] ?? '') ?></small>
        </div>
        <div class="field"><label for="password">Password <span class="muted">(minimum 12 characters)</span></label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="200" required>
            <small class="field-error"><?= View::escape($errors['password'] ?? '') ?></small>
        </div>
        <div class="field"><label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            <small class="field-error"><?= View::escape($errors['password_confirmation'] ?? '') ?></small>
        </div>
        <button class="button primary auth-submit" type="submit">Create account</button>
    </form>
    <p class="auth-footnote">Already registered? <a href="<?= View::escape($baseUrl) ?>/index.php?r=auth/login">Sign in</a></p>
</section>
