<?php use App\Core\View; ?>
<section class="page-heading">
    <div><p class="eyebrow">WORKSPACE</p><h1><?= View::escape($title) ?></h1></div>
</section>
<section class="panel empty-state">
    <h2>Coming later</h2>
    <p>This section is planned for a later project phase.</p>
    <a class="button secondary" href="<?= View::escape($baseUrl) ?>/index.php?r=companies/index">Go to companies</a>
</section>
