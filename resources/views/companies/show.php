<?php use App\Core\View; ?>
<section class="page-heading">
    <div><p class="eyebrow">COMPANIES</p><h1>Company details</h1></div>
    <div class="heading-actions">
        <a class="button secondary" href="<?= View::escape($baseUrl) ?>/index.php?r=companies/edit&amp;id=<?= (int) $company['company_id'] ?>">Edit company</a>
        <a class="button secondary" href="<?= View::escape($baseUrl) ?>/index.php?r=companies/index">Back</a>
    </div>
</section>
<section class="panel details-panel">
    <dl class="details-grid">
        <div><dt>Company name</dt><dd><?= View::escape($company['company_name']) ?></dd></div>
        <div><dt>GSTIN</dt><dd><?= View::escape($company['gstin'] ?? '—') ?></dd></div>
        <div><dt>Phone</dt><dd><?= View::escape($company['phone'] ?? '—') ?></dd></div>
        <div><dt>Email</dt><dd><?= View::escape($company['email'] ?? '—') ?></dd></div>
        <div class="full-width"><dt>Address</dt><dd><?= nl2br(View::escape($company['address'] ?? '—')) ?></dd></div>
        <div><dt>Created</dt><dd><?= View::escape($company['created_at']) ?></dd></div>
    </dl>
</section>
