<?php use App\Core\View; ?>
<header class="topbar">
    <a class="brand" href="<?= View::escape($baseUrl) ?>/index.php?r=dashboard">Billing<span>Desk</span></a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="sidebar">Menu</button>
    <div class="topbar-account">
        <form class="business-switcher" method="post" action="<?= View::escape($baseUrl) ?>/index.php?r=businesses/switch">
            <input type="hidden" name="_csrf" value="<?= View::escape($csrf) ?>">
            <label class="sr-only" for="active-business">Active business</label>
            <select id="active-business" name="business_id" aria-label="Active business">
                <?php foreach ($ownedBusinesses as $business): ?>
                    <option value="<?= (int) $business['business_id'] ?>" <?= (int) $business['business_id'] === \App\Core\BusinessContext::id() ? 'selected' : '' ?>>
                        <?= View::escape($business['business_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="button secondary small-button" type="submit">Switch</button>
        </form>
        <span class="topbar-note"><?= View::escape($userName) ?></span>
        <form method="post" action="<?= View::escape($baseUrl) ?>/index.php?r=auth/logout">
            <input type="hidden" name="_csrf" value="<?= View::escape($csrf) ?>">
            <button class="button secondary small-button" type="submit">Sign out</button>
        </form>
    </div>
</header>
