<?php use App\Core\View; ?>
<section class="auth-card">
    <p class="eyebrow">WELCOME BACK</p>
    <h1>Sign in</h1>
    <p class="muted">Access your billing workspace securely.</p>
    <form method="post" action="<?= View::escape($baseUrl) ?>/index.php?r=auth/login">
        <input type="hidden" name="_csrf" value="<?= View::escape($csrf) ?>">
        <div class="field"><label for="email">Email address</label>
            <input id="email" name="email" type="email" autocomplete="username" required maxlength="254">
        </div>
        <div class="field"><label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="button primary auth-submit" type="submit">Sign in</button>
    </form>
    <div class="auth-links">
        <a href="<?= View::escape($baseUrl) ?>/index.php?r=auth/forgot-password">Forgot password?</a>
        <a href="<?= View::escape($baseUrl) ?>/index.php?r=auth/register">Create an account</a>
    </div>
</section>
