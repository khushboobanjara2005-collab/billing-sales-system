<?php
use App\Core\Csrf;
use App\Core\View;
?>
<section class="page-heading no-print">
    <div><p class="eyebrow">INVOICE</p><h1><?= View::escape($invoice['invoice_number']) ?></h1></div>
    <div class="heading-actions">
        <button class="button primary" type="button" onclick="window.print()">Print / Save PDF</button>
        <?php if ($invoice['payment_status'] !== 'Paid'): ?>
            <a class="button primary" href="<?= View::escape($baseUrl) ?>/index.php?r=payments/create&amp;invoice_id=<?= (int) $invoice['invoice_id'] ?>">Record payment</a>
        <?php endif; ?>
        <a class="button secondary" href="<?= View::escape($baseUrl) ?>/index.php?r=invoices/edit&amp;id=<?= (int) $invoice['invoice_id'] ?>">Edit</a>
        <a class="button secondary" href="<?= View::escape($baseUrl) ?>/index.php?r=invoices/index">Back</a>
    </div>
</section>
<article class="panel print-invoice">
    <header class="invoice-paper-header"><div><p class="eyebrow">BILLING AND SALES</p><h1>INVOICE</h1></div>
        <div><strong><?= View::escape($invoice['invoice_number']) ?></strong><br>Date: <?= View::escape($invoice['invoice_date']) ?></div>
    </header>
    <section class="bill-to"><h2>Bill to</h2><strong><?= View::escape($invoice['company_name']) ?></strong>
        <?php if (!empty($invoice['gstin'])): ?><div>GSTIN: <?= View::escape($invoice['gstin']) ?></div><?php endif; ?>
        <?php if (!empty($invoice['address'])): ?><div><?= nl2br(View::escape($invoice['address'])) ?></div><?php endif; ?>
        <?php if (!empty($invoice['email'])): ?><div><?= View::escape($invoice['email']) ?></div><?php endif; ?>
    </section>
    <div class="table-wrap"><table><thead><tr><th>Description</th><th>Quantity</th><th>Rate</th><th>Amount</th></tr></thead>
        <tbody><?php foreach ($items as $item): ?><tr>
            <td data-label="Description"><?= View::escape($item['description']) ?></td>
            <td data-label="Quantity"><?= View::escape($item['quantity']) ?></td>
            <td data-label="Rate">₹<?= View::escape($item['rate']) ?></td>
            <td data-label="Amount">₹<?= View::escape($item['line_total']) ?></td>
        </tr><?php endforeach; ?></tbody>
    </table></div>
    <dl class="invoice-totals">
        <div><dt>Subtotal</dt><dd>₹<?= View::escape($invoice['subtotal']) ?></dd></div>
        <div><dt>CGST (<?= View::escape($invoice['cgst_rate']) ?>%)</dt><dd>₹<?= View::escape($invoice['cgst']) ?></dd></div>
        <div><dt>SGST (<?= View::escape($invoice['sgst_rate']) ?>%)</dt><dd>₹<?= View::escape($invoice['sgst']) ?></dd></div>
        <div class="grand-total"><dt>Total</dt><dd>₹<?= View::escape($invoice['total_amount']) ?></dd></div>
        <div><dt>Paid</dt><dd>₹<?= View::escape($invoice['paid_amount']) ?></dd></div>
        <div><dt>Pending</dt><dd>₹<?= View::escape($invoice['pending_amount']) ?></dd></div>
        <div><dt>Status</dt><dd><?= View::escape($invoice['payment_status']) ?></dd></div>
    </dl>
</article>
