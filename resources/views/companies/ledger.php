<?php use App\Core\View; ?>
<section class="page-heading">
    <div><p class="eyebrow">COMPANY LEDGER</p><h1><?= View::escape($company['company_name']) ?></h1></div>
    <a class="button secondary" href="<?= View::escape($baseUrl) ?>/index.php?r=companies/show&amp;id=<?= (int) $company['company_id'] ?>">Back to company</a>
</section>
<section class="summary-grid">
    <article class="panel"><span>Total invoiced</span><strong>₹<?= View::escape($totals['total']) ?></strong></article>
    <article class="panel"><span>Total paid</span><strong>₹<?= View::escape($totals['paid']) ?></strong></article>
    <article class="panel"><span>Outstanding</span><strong>₹<?= View::escape($totals['pending']) ?></strong></article>
</section>
<section class="panel">
    <form class="filter-form" method="get" action="<?= View::escape($baseUrl) ?>/index.php">
        <input type="hidden" name="r" value="companies/ledger"><input type="hidden" name="id" value="<?= (int) $company['company_id'] ?>">
        <label>From<input type="date" name="date_from" value="<?= View::escape($filters['date_from']) ?>"></label>
        <label>To<input type="date" name="date_to" value="<?= View::escape($filters['date_to']) ?>"></label>
        <label>Status<select name="status"><option value="">All</option>
            <?php foreach (['Unpaid', 'Partial', 'Paid'] as $status): ?><option <?= $filters['status'] === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?>
        </select></label>
        <button class="button secondary" type="submit">Filter</button>
    </form>
    <div class="table-wrap"><table><thead><tr><th>Date</th><th>Invoice</th><th>Total</th><th>Paid</th><th>Pending</th><th>Status</th><th>Running outstanding</th></tr></thead>
        <tbody><?php foreach ($rows as $row): ?><tr>
            <td data-label="Date"><?= View::escape($row['invoice_date']) ?></td>
            <td data-label="Invoice"><a href="<?= View::escape($baseUrl) ?>/index.php?r=invoices/show&amp;id=<?= (int) $row['invoice_id'] ?>"><?= View::escape($row['invoice_number']) ?></a></td>
            <td data-label="Total">₹<?= View::escape($row['total_amount']) ?></td>
            <td data-label="Paid">₹<?= View::escape($row['paid_amount']) ?></td>
            <td data-label="Pending">₹<?= View::escape($row['pending_amount']) ?></td>
            <td data-label="Status"><?= View::escape($row['payment_status']) ?></td>
            <td data-label="Running outstanding">₹<?= View::escape($row['running_balance']) ?></td>
        </tr><?php endforeach; ?>
        <?php if ($rows === []): ?><tr><td colspan="7" class="empty-cell">No invoices match these filters.</td></tr><?php endif; ?></tbody>
    </table></div>
</section>
