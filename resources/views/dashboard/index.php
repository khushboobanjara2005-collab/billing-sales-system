<?php
use App\Core\View;
use App\Helpers\Money;
?>
<section class="page-heading">
    <div><p class="eyebrow">OVERVIEW</p><h1>Dashboard</h1><p class="muted">Sales and collections at a glance.</p></div>
    <a class="button secondary no-print" href="<?= View::escape($baseUrl) ?>/index.php?r=reports">View reports</a>
</section>
<section class="summary-grid dashboard-cards" aria-label="Business summary">
    <article class="panel"><span>Total sales</span><strong><?= Money::format((string) $totals['sales']) ?></strong></article>
    <article class="panel"><span>Total received</span><strong><?= Money::format((string) $totals['received']) ?></strong></article>
    <article class="panel"><span>Total pending</span><strong><?= Money::format((string) $totals['pending']) ?></strong></article>
    <article class="panel"><span>Companies</span><strong><?= (int) $totals['companies'] ?></strong></article>
    <article class="panel"><span>Invoices</span><strong><?= (int) $totals['invoices'] ?></strong></article>
</section>
<section class="panel report-panel">
    <div class="report-heading"><h2>Monthly sales · last 6 months</h2><button class="button secondary no-print" type="button" onclick="window.print()">Print</button></div>
    <canvas id="sales-chart" height="250" role="img" aria-label="Monthly sales bar chart for the last six months"></canvas>
    <table class="chart-data"><caption>Monthly sales amounts</caption><thead><tr><th>Month</th><th>Sales</th></tr></thead>
        <tbody><?php foreach ($months as $month): ?><tr><td><?= View::escape($month['label']) ?></td><td><?= Money::format((string) $month['sales']) ?></td></tr><?php endforeach; ?></tbody>
    </table>
</section>
<section class="panel report-panel">
    <div class="report-heading"><h2>5 most recent invoices</h2><a href="<?= View::escape($baseUrl) ?>/index.php?r=invoices/index">All invoices</a></div>
    <div class="table-wrap"><table><thead><tr><th>Invoice</th><th>Date</th><th>Company</th><th>Total</th><th>Received</th></tr></thead>
        <tbody><?php foreach ($recent as $invoice): ?><tr>
            <td data-label="Invoice"><a href="<?= View::escape($baseUrl) ?>/index.php?r=invoices/show&amp;id=<?= (int) $invoice['invoice_id'] ?>"><?= View::escape($invoice['invoice_number']) ?></a></td>
            <td data-label="Date"><?= View::escape($invoice['invoice_date']) ?></td>
            <td data-label="Company"><?= View::escape($invoice['company_name']) ?></td>
            <td data-label="Total"><?= Money::format((string) $invoice['total_amount']) ?></td>
            <td data-label="Received"><?= Money::format((string) $invoice['paid_amount']) ?></td>
        </tr><?php endforeach; ?>
        <?php if ($recent === []): ?><tr><td colspan="5" class="empty-cell">No invoices yet.</td></tr><?php endif; ?></tbody>
    </table></div>
</section>
<script>
window.dashboardSales = <?= json_encode($months, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= View::escape($baseUrl) ?>/assets/js/dashboard-chart.js" defer></script>
