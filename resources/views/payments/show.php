<?php use App\Core\Csrf; use App\Core\View; ?>
<section class="page-heading">
    <div><p class="eyebrow">PAYMENT</p><h1>Payment details</h1></div>
    <a class="button secondary" href="<?= View::escape($baseUrl) ?>/index.php?r=payments/index">Back to payments</a>
</section>
<section class="panel details-panel">
    <dl class="details-grid">
        <div><dt>Company</dt><dd><?= View::escape($payment['company_name']) ?></dd></div>
        <div><dt>Invoice</dt><dd><a href="<?= View::escape($baseUrl) ?>/index.php?r=invoices/show&amp;id=<?= (int) $payment['invoice_id'] ?>"><?= View::escape($payment['invoice_number']) ?></a></dd></div>
        <div><dt>Payment date</dt><dd><?= View::escape($payment['payment_date']) ?></dd></div>
        <div><dt>Method</dt><dd><?= View::escape($payment['payment_method']) ?></dd></div>
        <div><dt>Amount</dt><dd>₹<?= View::escape($payment['amount']) ?></dd></div>
    </dl>
    <form class="form-actions" method="post" action="<?= View::escape($baseUrl) ?>/index.php?r=payments/delete" data-confirm="Delete this payment record? The invoice balance will increase.">
        <input type="hidden" name="_csrf" value="<?= View::escape(Csrf::token()) ?>">
        <input type="hidden" name="payment_id" value="<?= (int) $payment['payment_id'] ?>">
        <button class="button secondary danger-text" type="submit">Delete payment</button>
    </form>
</section>
