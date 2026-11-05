<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Company;
use App\Models\Business;
use DomainException;
use InvalidArgumentException;
use PDO;
use Throwable;

final class InvoiceService
{
    private Invoice $invoices;
    private InvoiceItem $items;
    private Payment $payments;
    private Company $companies;
    private Business $businesses;
    private const MAX_AMOUNT_PAISE = 999999999999;

    public function __construct(private PDO $pdo) {
        $this->invoices = new Invoice($pdo);
        $this->items = new InvoiceItem($pdo);
        $this->payments = new Payment($pdo);
        $this->companies = new Company($pdo);
        $this->businesses = new Business($pdo);
    }
    public function create(int $businessId, array $data): int {
        $calculated = $this->calculate($businessId, $data);
        $this->pdo->beginTransaction();
        try {
            $business = $this->businesses->findForUpdate($businessId);
            if ($business === null) throw new DomainException('Business not found.');
            $nextNumber = (int) $business['next_invoice_number'];
            $invoice = $this->invoiceValues($businessId, $data, $calculated);
            $invoice['invoice_number'] = sprintf(
                '%s-%d-%04d', $business['invoice_prefix'], (int) substr($data['invoice_date'], 0, 4), $nextNumber
            );
            $this->businesses->advanceInvoiceNumber($businessId, $nextNumber + 1);
            $id = $this->invoices->create($invoice);
            $this->items->replaceForInvoice($id, $calculated['items']);
            $this->pdo->commit();
            return $id;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        }
    }
    public function update(int $businessId, int $invoiceId, array $data): void {
        $calculated = $this->calculate($businessId, $data);
        $this->pdo->beginTransaction();
        try {
            $existing = $this->invoices->findForUpdate($businessId, $invoiceId);
            if ($existing === null) throw new DomainException('Invoice not found.');
            $paid = $this->toPaise($this->payments->totalForInvoice($invoiceId));
            if ($calculated['total_paise'] < $paid) {
                throw new DomainException('Invoice total cannot be less than payments already received.');
            }
            $this->invoices->update($businessId, $invoiceId, $this->invoiceValues($businessId, $data, $calculated));
            $this->items->replaceForInvoice($invoiceId, $calculated['items']);
            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        }
    }
    public function delete(int $businessId, int $invoiceId): void {
        $this->pdo->beginTransaction();
        try {
            if ($this->invoices->findForUpdate($businessId, $invoiceId) === null) {
                throw new DomainException('Invoice not found.');
            }
            if ($this->payments->existsForInvoice($invoiceId)) {
                throw new DomainException('This invoice has payments.');
            }
            $this->invoices->delete($businessId, $invoiceId);
            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        }
    }
    private function calculate(int $businessId, array $data): array {
        $taxType = $this->resolveTaxType($businessId, $data);
        $subtotal = 0;
        $items = [];
        foreach ($data['items'] as $item) {
            $quantity = $this->scaled($item['quantity'], 3);
            $rate = $this->toPaise($item['rate']);
            if ($quantity < 1) throw new InvalidArgumentException('Quantity must be greater than zero.');
            if ($rate > 0 && $quantity > intdiv(self::MAX_AMOUNT_PAISE * 1000, $rate)) {
                throw new InvalidArgumentException('An invoice item amount is too large.');
            }
            $lineTotal = intdiv($quantity * $rate + 500, 1000);
            if ($lineTotal > self::MAX_AMOUNT_PAISE - $subtotal) {
                throw new InvalidArgumentException('Invoice subtotal exceeds the allowed amount.');
            }
            $subtotal += $lineTotal;
            $items[] = [
                'weigh_bridge_serial' => $item['weigh_bridge_serial'],
                'item_date' => $item['item_date'],
                'description' => $item['description'],
                'quantity' => $this->decimal($quantity, 3),
                'rate' => $this->money($rate),
                'line_total' => $this->money($lineTotal),
            ];
        }
        $cgstRate = $this->scaled($data['cgst_rate'], 2);
        $sgstRate = $this->scaled($data['sgst_rate'], 2);
        $cgst = $taxType === 'intra' ? $this->tax($subtotal, $cgstRate) : 0;
        $sgst = $taxType === 'intra' ? $this->tax($subtotal, $sgstRate) : 0;
        $igst = $taxType === 'inter' ? $this->tax($subtotal, $cgstRate + $sgstRate) : 0;
        $taxTotal = $cgst + $sgst + $igst;
        if ($taxTotal > self::MAX_AMOUNT_PAISE - $subtotal) {
            throw new InvalidArgumentException('Invoice total exceeds the allowed amount.');
        }
        $beforeRoundOff = $subtotal + $taxTotal;
        $total = intdiv($beforeRoundOff + 50, 100) * 100;
        $roundOff = $total - $beforeRoundOff;
        return [
            'items' => $items, 'subtotal' => $subtotal, 'cgst' => $cgst, 'sgst' => $sgst,
            'igst' => $igst, 'tax_type' => $taxType, 'round_off' => $roundOff,
            'total_paise' => $total,
        ];
    }
    private function invoiceValues(int $businessId, array $data, array $totals): array {
        return [
            'business_id' => $businessId, 'company_id' => (int) $data['company_id'],
            'invoice_date' => $data['invoice_date'],
            'subtotal' => $this->money($totals['subtotal']),
            'cgst_rate' => $this->rate($data['cgst_rate']), 'sgst_rate' => $this->rate($data['sgst_rate']),
            'tax_type' => $totals['tax_type'],
            'cgst' => $this->money($totals['cgst']), 'sgst' => $this->money($totals['sgst']),
            'igst' => $this->money($totals['igst']), 'round_off' => $this->signedMoney($totals['round_off']),
            'total_amount' => $this->money($totals['total_paise']),
        ];
    }
    private function resolveTaxType(int $businessId, array $data): string {
        $company = $this->companies->find($businessId, (int) $data['company_id']);
        if ($company === null) throw new DomainException('Choose an existing company.');
        $sellerGstin = (string) ($this->businesses->findById($businessId)['gstin'] ?? '');
        $customerGstin = (string) ($company['gstin'] ?? '');
        if (preg_match('/^[0-9]{2}/', $sellerGstin, $sellerState)
            && preg_match('/^[0-9]{2}/', $customerGstin, $customerState)) {
            return $sellerState[0] === $customerState[0] ? 'intra' : 'inter';
        }
        if (!in_array($data['tax_type'], ['intra', 'inter'], true)) {
            throw new InvalidArgumentException('Choose the GST type because a seller or customer GSTIN is missing.');
        }
        return $data['tax_type'];
    }
    private function tax(int $subtotal, int $rateHundredths): int {
        return intdiv($subtotal * $rateHundredths + 5000, 10000);
    }
    private function scaled(string $value, int $places): int {
        $parts = explode('.', $value, 2);
        $fraction = str_pad($parts[1] ?? '', $places, '0');
        return ((int) $parts[0] * (10 ** $places)) + (int) substr($fraction, 0, $places);
    }
    private function toPaise(string $amount): int { return $this->scaled($amount, 2); }
    private function rate(string $rate): string { return $this->decimal($this->scaled($rate, 2), 2); }
    private function money(int $paise): string { return $this->decimal($paise, 2); }
    private function signedMoney(int $paise): string {
        return ($paise < 0 ? '-' : '') . $this->money(abs($paise));
    }
    private function decimal(int $value, int $places): string {
        $unit = 10 ** $places;
        return intdiv($value, $unit) . '.' . str_pad((string) ($value % $unit), $places, '0', STR_PAD_LEFT);
    }
}
