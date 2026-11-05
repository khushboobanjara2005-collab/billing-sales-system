<?php
use App\Core\View;
use App\Helpers\Money;
?>
<section class="page-heading">
    <div><p class="eyebrow">ANALYSIS</p><h1>Reports</h1><p class="muted">Review sales, outstanding balances, and payment methods.</p></div>
</section>
<section class="panel no-print">
    <form class="filter-form" method="get" action="<?= View::escape($baseUrl) ?>/index.php">
        <input type="hidden" name="r" value="reports">
        <label>From<input type="date" name="date_from" value="<?= View::escape($dateFrom) ?>"></label>
        <label>To<input type="date" name="date_to" value="<?= View::escape($dateTo) ?>"></label>
        <button class="button secondary" type="submit">Apply date range</button>
    </form>
    <p class="muted">The range selects invoices by invoice date; received and pending include their full payment history. Payment methods use payment date, and outstanding balances are current.</p>
    <?php if ($dateError !== ''): ?><p class="flash error" role="alert"><?= View::escape($dateError) ?></p><?php endif; ?>
</section>
<section class="panel report-panel print-report">
    <div class="report-heading"><div><p class="eyebrow">REPORT A</p><h2>Sales summary</h2></div><button class="button secondary no-print" type="button" onclick="window.print()">Print report</button></div>
    <p class="muted">Invoice dates: <?= View::escape($dateFrom ?: 'Any') ?> to <?= View::escape($dateTo ?: 'Any') ?></p>
    <dl class="report-totals">
        <div><dt>Invoices</dt><dd><?= (int) $sales['invoice_count'] ?></dd></div>
        <div><dt>Total sales</dt><dd><?= Money::format((string) $sales['sales']) ?></dd></div>
        <div><dt>Received</dt><dd><?= Money::format((string) $sales['received']) ?></dd></div>
        <div><dt>Pending</dt><dd><?= Money::format((string) $sales['pending']) ?></dd></div>
    </dl>
</section>
<section class="panel report-panel print-report">
    <div class="report-heading"><div><p class="eyebrow">REPORT B</p><h2>Company outstanding</h2></div><button class="button secondary no-print" type="button" onclick="window.print()">Print report</button></div>
    <p class="muted">Current balances, sorted from largest outstanding to smallest.</p>
    <div class="table-wrap"><table><thead><tr><th>Company</th><th>Total sales</th><th>Received</th><th>Pending</th></tr></thead><tbody>
        <?php foreach ($companies as $company): ?><tr>
            <td data-label="Company"><a href="<?= View::escape($baseUrl) ?>/index.php?r=companies/ledger&amp;id=<?= (int) $company['company_id'] ?>"><?= View::escape($company['company_name']) ?></a></td>
            <td data-label="Total sales"><?= Money::format((string) $company['sales']) ?></td>
            <td data-label="Received"><?= Money::format((string) $company['received']) ?></td>
            <td data-label="Pending"><?= Money::format((string) $company['pending']) ?></td>
        </tr><?php endforeach; ?>
        <?php if ($companies === []): ?><tr><td colspan="4" class="empty-cell">No outstanding balances.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<section class="panel report-panel print-report">
    <div class="report-heading"><div><p class="eyebrow">REPORT C</p><h2>Payment method summary</h2></div><button class="button secondary no-print" type="button" onclick="window.print()">Print report</button></div>
    <p class="muted">Payment dates: <?= View::escape($dateFrom ?: 'Any') ?> to <?= View::escape($dateTo ?: 'Any') ?></p>
    <div class="table-wrap"><table><thead><tr><th>Method</th><th>Payments</th><th>Amount received</th></tr></thead><tbody>
        <?php foreach ($methods as $method): ?><tr>
            <td data-label="Method"><?= View::escape($method['payment_method']) ?></td>
            <td data-label="Payments"><?= (int) $method['payment_count'] ?></td>
            <td data-label="Amount received"><?= Money::format((string) $method['total']) ?></td>
        </tr><?php endforeach; ?>
        <?php if ($methods === []): ?><tr><td colspan="3" class="empty-cell">No payments match this date range.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
