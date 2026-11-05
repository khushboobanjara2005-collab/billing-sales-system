<?php
use App\Core\Csrf;
use App\Core\View;
?>
<section class="page-heading">
    <div><p class="eyebrow">CASH FLOW</p><h1>Payments</h1><p class="muted">Payment history across invoices.</p></div>
    <a class="button primary" href="<?= View::escape($baseUrl) ?>/index.php?r=payments/create">Record payment</a>
</section>
<section class="panel">
    <form class="filter-form" method="get" action="<?= View::escape($baseUrl) ?>/index.php">
        <input type="hidden" name="r" value="payments/index">
        <label>Company<select name="company_id"><option value="">All companies</option>
            <?php foreach ($companies as $company): ?><option value="<?= (int) $company['company_id'] ?>" <?= (int) $filters['company_id'] === (int) $company['company_id'] ? 'selected' : '' ?>><?= View::escape($company['company_name']) ?></option><?php endforeach; ?>
        </select></label>
        <label>Method<select name="method"><option value="">All methods</option>
            <?php foreach (['Cash', 'UPI', 'Bank Transfer', 'Cheque'] as $method): ?><option <?= $filters['method'] === $method ? 'selected' : '' ?>><?= $method ?></option><?php endforeach; ?>
        </select></label>
        <label>From<input type="date" name="date_from" value="<?= View::escape($filters['date_from']) ?>"></label>
        <label>To<input type="date" name="date_to" value="<?= View::escape($filters['date_to']) ?>"></label>
        <button class="button secondary" type="submit">Filter</button>
    </form>
    <p class="result-count"><?= (int) $total ?> payment<?= $total === 1 ? '' : 's' ?></p>
    <div class="table-wrap"><table><thead><tr><th>Date</th><th>Company</th><th>Invoice</th><th>Method</th><th>Amount</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $payment): ?><tr>
            <td data-label="Date"><?= View::escape($payment['payment_date']) ?></td>
            <td data-label="Company"><?= View::escape($payment['company_name']) ?></td>
            <td data-label="Invoice"><a href="<?= View::escape($baseUrl) ?>/index.php?r=invoices/show&amp;id=<?= (int) $payment['invoice_id'] ?>"><?= View::escape($payment['invoice_number']) ?></a></td>
            <td data-label="Method"><?= View::escape($payment['payment_method']) ?></td>
            <td data-label="Amount">₹<?= View::escape($payment['amount']) ?></td>
            <td data-label="Actions" class="actions">
                <a href="<?= View::escape($baseUrl) ?>/index.php?r=payments/show&amp;id=<?= (int) $payment['payment_id'] ?>">View</a>
                <form method="post" action="<?= View::escape($baseUrl) ?>/index.php?r=payments/delete" data-confirm="Delete this payment record? The invoice balance will increase.">
                    <input type="hidden" name="_csrf" value="<?= View::escape(Csrf::token()) ?>">
                    <input type="hidden" name="payment_id" value="<?= (int) $payment['payment_id'] ?>">
                    <button class="link-button danger-text" type="submit">Delete</button>
                </form>
            </td>
        </tr><?php endforeach; ?>
        <?php if ($payments === []): ?><tr><td class="empty-cell" colspan="6">No payments found.</td></tr><?php endif; ?>
        </tbody></table></div>
    <?php if ($pages > 1): ?><nav class="pagination" aria-label="Payment pages">
        <?php for ($number = 1; $number <= $pages; $number++): ?>
            <a class="<?= $number === $page ? 'current' : '' ?>" href="<?= View::escape($baseUrl) ?>/index.php?r=payments/index&amp;company_id=<?= (int) $filters['company_id'] ?>&amp;method=<?= rawurlencode($filters['method']) ?>&amp;date_from=<?= rawurlencode($filters['date_from']) ?>&amp;date_to=<?= rawurlencode($filters['date_to']) ?>&amp;page=<?= $number ?>"><?= $number ?></a>
        <?php endfor; ?>
    </nav><?php endif; ?>
</section>
