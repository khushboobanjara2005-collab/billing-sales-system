<?php
use App\Core\Csrf;
use App\Core\View;
$query = $filters;
$query['r'] = 'invoices/index';
?>
<section class="page-heading">
    <div><p class="eyebrow">BILLING</p><h1>Invoices</h1><p class="muted">Create and track customer invoices.</p></div>
    <div class="heading-actions">
        <a class="button secondary" href="<?= View::escape($baseUrl) ?>/index.php?r=seller-profile/edit">Seller Profile</a>
        <a class="button primary" href="<?= View::escape($baseUrl) ?>/index.php?r=invoices/create">Create invoice</a>
    </div>
</section>
<section class="panel">
    <form class="filter-form" method="get" action="<?= View::escape($baseUrl) ?>/index.php">
        <input type="hidden" name="r" value="invoices/index">
        <label>Invoice number<input name="number" value="<?= View::escape($filters['number'] ?? '') ?>" placeholder="Search number"></label>
        <label>Company<select name="company_id"><option value="">All companies</option>
            <?php foreach ($companies as $company): ?>
                <option value="<?= (int) $company['company_id'] ?>" <?= (int) ($filters['company_id'] ?? 0) === (int) $company['company_id'] ? 'selected' : '' ?>><?= View::escape($company['company_name']) ?></option>
            <?php endforeach; ?>
        </select></label>
        <label>From<input type="date" name="date_from" value="<?= View::escape($filters['date_from'] ?? '') ?>"></label>
        <label>To<input type="date" name="date_to" value="<?= View::escape($filters['date_to'] ?? '') ?>"></label>
        <label>Status<select name="status"><option value="">All</option>
            <?php foreach (['Unpaid', 'Partial', 'Paid'] as $status): ?>
                <option <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= $status ?></option>
            <?php endforeach; ?>
        </select></label>
        <button class="button secondary" type="submit">Filter</button>
    </form>
    <p class="result-count"><?= (int) $total ?> invoice<?= $total === 1 ? '' : 's' ?></p>
    <div class="table-wrap">
        <table><thead><tr><th>Number</th><th>Date</th><th>Company</th><th>Total</th><th>Paid</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($invoices as $invoice): ?>
                <tr>
                    <td data-label="Number"><?= View::escape($invoice['invoice_number']) ?></td>
                    <td data-label="Date"><?= View::escape($invoice['invoice_date']) ?></td>
                    <td data-label="Company"><?= View::escape($invoice['company_name']) ?></td>
                    <td data-label="Total">₹<?= View::escape($invoice['total_amount']) ?></td>
                    <td data-label="Paid">₹<?= View::escape($invoice['paid_amount']) ?></td>
                    <td data-label="Status"><span class="status <?= strtolower(View::escape($invoice['payment_status'])) ?>"><?= View::escape($invoice['payment_status']) ?></span></td>
                    <td data-label="Actions" class="actions">
                        <a href="<?= View::escape($baseUrl) ?>/index.php?r=invoices/show&amp;id=<?= (int) $invoice['invoice_id'] ?>">View</a>
                        <a href="<?= View::escape($baseUrl) ?>/index.php?r=invoices/edit&amp;id=<?= (int) $invoice['invoice_id'] ?>">Edit</a>
                        <form method="post" action="<?= View::escape($baseUrl) ?>/index.php?r=invoices/delete" data-confirm="Delete this invoice?">
                            <input type="hidden" name="_csrf" value="<?= View::escape(Csrf::token()) ?>">
                            <input type="hidden" name="invoice_id" value="<?= (int) $invoice['invoice_id'] ?>">
                            <button class="link-button danger-text" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($invoices === []): ?><tr><td colspan="7" class="empty-cell">No invoices found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?><nav class="pagination" aria-label="Invoice pages">
        <?php for ($number = 1; $number <= $pages; $number++): $query['page'] = $number; ?>
            <a class="<?= $number === $page ? 'current' : '' ?>" href="<?= View::escape($baseUrl) ?>/index.php?<?= View::escape(http_build_query($query)) ?>"><?= $number ?></a>
        <?php endfor; ?>
    </nav><?php endif; ?>
</section>
