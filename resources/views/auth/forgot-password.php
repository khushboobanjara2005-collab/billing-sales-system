<?php use App\Core\View; ?>
<section class="auth-card">
    <p class="eyebrow">ACCOUNT HELP</p>
    <h1>Password help</h1>
    <p class="muted">Enter the account email address. This local installation does not have email delivery configured.</p>
    <form method="post" action="<?= View::escape($baseUrl) ?>/index.php?r=auth/forgot-password">
        <input type="hidden" name="_csrf" value="<?= View::escape($csrf) ?>">
        <div class="field"><label for="email">Email address</label>
            <input id="email" name="email" type="email" autocomplete="email" required maxlength="254">
        </div>
        <button class="button primary auth-submit" type="submit">Continue</button>
    </form>
    <p class="auth-footnote"><a href="<?= View::escape($baseUrl) ?>/index.php?r=auth/login">Return to sign in</a></p>
</section>
