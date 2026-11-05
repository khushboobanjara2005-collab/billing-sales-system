<?php
use App\Core\Csrf;
use App\Core\View;
?>
<section class="page-heading">
    <div><p class="eyebrow">DIRECTORY</p><h1>Companies</h1><p class="muted">Manage company contact and GST details.</p></div>
    <a class="button primary" href="<?= View::escape($baseUrl) ?>/index.php?r=companies/create">Add company</a>
</section>
<section class="panel">
    <form class="search-form" method="get" action="<?= View::escape($baseUrl) ?>/index.php">
        <input type="hidden" name="r" value="companies/index">
        <label class="sr-only" for="company-search">Search companies</label>
        <input id="company-search" type="search" name="q" value="<?= View::escape($search) ?>"
               placeholder="Search name, GSTIN, phone or email">
        <button class="button secondary" type="submit">Search</button>
        <?php if ($search !== ''): ?><a class="text-link" href="<?= View::escape($baseUrl) ?>/index.php?r=companies/index">Clear</a><?php endif; ?>
    </form>
    <p class="result-count"><?= (int) $total ?> result<?= $total === 1 ? '' : 's' ?></p>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Company</th><th>GSTIN</th><th>Phone</th><th>Email</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($companies as $company): ?>
                <tr>
                    <td data-label="Company"><?= View::escape($company['company_name']) ?></td>
                    <td data-label="GSTIN"><?= View::escape($company['gstin'] ?? '—') ?></td>
                    <td data-label="Phone"><?= View::escape($company['phone'] ?? '—') ?></td>
                    <td data-label="Email"><?= View::escape($company['email'] ?? '—') ?></td>
                    <td data-label="Actions" class="actions">
                        <a href="<?= View::escape($baseUrl) ?>/index.php?r=companies/show&amp;id=<?= (int) $company['company_id'] ?>">View</a>
                        <a href="<?= View::escape($baseUrl) ?>/index.php?r=companies/edit&amp;id=<?= (int) $company['company_id'] ?>">Edit</a>
                        <form method="post" action="<?= View::escape($baseUrl) ?>/index.php?r=companies/delete" data-confirm="Delete this company?">
                            <input type="hidden" name="_csrf" value="<?= View::escape(Csrf::token()) ?>">
                            <input type="hidden" name="company_id" value="<?= (int) $company['company_id'] ?>">
                            <button class="link-button danger-text" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($companies === []): ?><tr><td colspan="5" class="empty-cell">No companies found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Company pages">
            <?php for ($number = 1; $number <= $pages; $number++): ?>
                <a class="<?= $number === $page ? 'current' : '' ?>"
                   href="<?= View::escape($baseUrl) ?>/index.php?r=companies/index&amp;q=<?= rawurlencode($search) ?>&amp;page=<?= $number ?>"
                   <?= $number === $page ? 'aria-current="page"' : '' ?>><?= $number ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</section>
