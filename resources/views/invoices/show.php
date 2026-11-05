<?php
use App\Core\View;
use App\Helpers\Money;
$igstRate = Money::sumRates((string) $invoice['cgst_rate'], (string) $invoice['sgst_rate']);
$terms = trim((string) ($seller['terms_and_conditions'] ?? ''));
if ($terms === '') {
    $jurisdiction = trim((string) ($seller['jurisdiction'] ?? ''));
    $terms = "1. All disputes subject to {$jurisdiction} jurisdiction.\n"
        . "2. Goods once sold will not be taken back.\n"
        . "3. Interest @ 25% will be charged if payment is not made within 7 days.\n"
        . "4. Errors and omissions excepted.";
}
?>
<section class="page-heading no-print">
    <div><p class="eyebrow">SALES BILL</p><h1><?= View::escape($invoice['invoice_number']) ?></h1></div>
    <div class="heading-actions">
        <button class="button primary" type="button" onclick="window.print()">Print Invoice</button>
        <?php if ($invoice['payment_status'] !== 'Paid'): ?>
            <a class="button primary" href="<?= View::escape($baseUrl) ?>/index.php?r=payments/create&amp;invoice_id=<?= (int) $invoice['invoice_id'] ?>">Record payment</a>
        <?php endif; ?>
        <a class="button secondary" href="<?= View::escape($baseUrl) ?>/index.php?r=invoices/edit&amp;id=<?= (int) $invoice['invoice_id'] ?>">Edit</a>
        <a class="button secondary" href="<?= View::escape($baseUrl) ?>/index.php?r=invoices/index">Back</a>
    </div>
</section>
<article class="panel print-invoice invoice-document <?= count($items) <= 5 ? 'invoice-short' : '' ?>">
    <header class="invoice-brand-header">
        <div class="seller-block">
            <h1 class="seller-name"><?= View::escape($seller['business_name'] ?? '') ?></h1>
            <?php if (!empty($seller['tagline'])): ?><p class="invoice-subtitle"><?= View::escape($seller['tagline']) ?></p><?php endif; ?>
            <?php if (!empty($seller['address'])): ?><p class="seller-contact"><?= View::escape($seller['address']) ?><?= !empty($seller['city']) ? ', ' . View::escape($seller['city']) : '' ?><?= !empty($seller['state']) ? ', ' . View::escape($seller['state']) : '' ?><?= !empty($seller['pincode']) ? ' - ' . View::escape($seller['pincode']) : '' ?></p><?php endif; ?>
            <?php if (!empty($seller['phone'])): ?><p class="seller-contact">Phone: <?= View::escape($seller['phone']) ?></p><?php endif; ?>
            <?php if (!empty($seller['email'])): ?><p class="seller-contact">Email: <?= View::escape($seller['email']) ?></p><?php endif; ?>
            <?php if (!empty($seller['gstin'])): ?><p class="seller-contact">GSTIN: <?= View::escape($seller['gstin']) ?></p><?php endif; ?>
        </div>
        <div class="invoice-meta">
            <h2>TAX INVOICE</h2>
            <dl>
                <div><dt>Bill No.</dt><dd><?= View::escape($invoice['invoice_number']) ?></dd></div>
                <div><dt>Invoice Date</dt><dd><?= View::escape($invoice['invoice_date']) ?></dd></div>
            </dl>
        </div>
    </header>
    <section class="invoice-customer">
        <div class="customer-name">
            <p class="eyebrow">CUSTOMER NAME</p>
            <h2><?= View::escape($invoice['company_name']) ?></h2>
            <?php if (!empty($invoice['address'])): ?><p><?= nl2br(View::escape($invoice['address'])) ?></p><?php endif; ?>
            <?php if (!empty($invoice['phone'])): ?><p>Phone: <?= View::escape($invoice['phone']) ?></p><?php endif; ?>
            <?php if (!empty($invoice['email'])): ?><p>Email: <?= View::escape($invoice['email']) ?></p><?php endif; ?>
        </div>
        <div class="customer-details">
            <strong>Party GSTIN</strong>
            <span><?= View::escape($invoice['gstin'] ?: 'Not provided') ?></span>
        </div>
    </section>
    <div class="table-wrap invoice-lines-wrap">
        <table class="invoice-lines">
            <thead><tr><th>Weigh Bridge S.No.</th><th>Date</th><th>Description</th><th>Weight (kg)</th><th>Rate (₹/kg)</th><th>Amount (₹)</th></tr></thead>
            <tbody>
            <?php foreach ($items as $index => $item): ?>
                <tr>
                    <td data-label="Weigh Bridge S.No."><?= View::escape($item['weigh_bridge_serial'] ?: '-') ?></td>
                    <td data-label="Date"><?= View::escape($item['item_date'] ?: $invoice['invoice_date']) ?></td>
                    <td data-label="Description"><?= View::escape($item['description']) ?></td>
                    <td data-label="Weight (kg)"><?= View::escape($item['quantity']) ?></td>
                    <td data-label="Rate">₹<?= View::escape($item['rate']) ?></td>
                    <td data-label="Amount"><?= Money::format((string) $item['line_total']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <section class="invoice-bottom">
        <div class="invoice-words-box">
            <h2>Amount in Words</h2>
            <p><?= View::escape(Money::amountInWords((string) $invoice['total_amount'])) ?></p>
        </div>
        <dl class="invoice-totals">
            <div><dt>Subtotal</dt><dd><?= Money::format((string) $invoice['subtotal']) ?></dd></div>
            <?php if (($invoice['tax_type'] ?? 'intra') === 'intra'): ?>
                <div><dt>CGST @ <?= View::escape($invoice['cgst_rate']) ?>%</dt><dd><?= Money::format((string) $invoice['cgst']) ?></dd></div>
                <div><dt>SGST @ <?= View::escape($invoice['sgst_rate']) ?>%</dt><dd><?= Money::format((string) $invoice['sgst']) ?></dd></div>
            <?php else: ?>
                <div><dt>IGST @ <?= View::escape($igstRate) ?>%</dt><dd><?= Money::format((string) $invoice['igst']) ?></dd></div>
                <div class="tax-na"><dt>CGST / SGST</dt><dd>—</dd></div>
            <?php endif; ?>
            <div><dt>Round Off</dt><dd><?= Money::format((string) $invoice['round_off']) ?></dd></div>
            <div class="grand-total"><dt>Net Amount</dt><dd><?= Money::format((string) $invoice['total_amount']) ?></dd></div>
        </dl>
    </section>
    <section class="invoice-payment-status no-print">
        <span>Payment status: <strong><?= View::escape($invoice['payment_status']) ?></strong></span>
        <span>Paid: <strong><?= Money::format((string) $invoice['paid_amount']) ?></strong></span>
        <span>Pending: <strong><?= Money::format((string) $invoice['pending_amount']) ?></strong></span>
    </section>
    <section class="invoice-business-details">
        <div class="invoice-info-box">
            <h2>Bank Details</h2>
            <dl>
                <div><dt>Bank Name</dt><dd><?= View::escape($seller['bank_name'] ?? '') ?></dd></div>
                <div><dt>Branch</dt><dd><?= View::escape($seller['branch_name'] ?? '') ?></dd></div>
                <div><dt>Account Holder</dt><dd><?= View::escape($seller['account_holder_name'] ?? '') ?></dd></div>
                <div><dt>Account No.</dt><dd><?= View::escape($seller['account_number'] ?? '') ?></dd></div>
                <div><dt>IFSC Code</dt><dd><?= View::escape($seller['ifsc_code'] ?? '') ?></dd></div>
            </dl>
        </div>
        <div class="invoice-info-box">
            <h2>Terms &amp; Conditions</h2>
            <div class="invoice-terms"><?= nl2br(View::escape($terms)) ?></div>
        </div>
    </section>
    <footer class="invoice-signature">
        <div class="invoice-divider"></div>
        <strong>For <?= View::escape($seller['business_name'] ?? '') ?><br>
            <span><?= View::escape($seller['authorized_signatory'] ?? 'Authorized Signatory') ?></span></strong>
    </footer>
</article>
