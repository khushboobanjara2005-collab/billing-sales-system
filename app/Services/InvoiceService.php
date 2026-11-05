<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use DomainException;
use InvalidArgumentException;
use PDO;
use Throwable;

final class InvoiceService
{
    private Invoice $invoices;
    private InvoiceItem $items;
    private Payment $payments;
    private const MAX_AMOUNT_PAISE = 999999999999;

    public function __construct(private PDO $pdo) {
        $this->invoices = new Invoice($pdo);
        $this->items = new InvoiceItem($pdo);
        $this->payments = new Payment($pdo);
    }
    public function create(array $data): int {
        $calculated = $this->calculate($data);
        $this->pdo->beginTransaction();
        try {
            $this->lockNumberSequence();
            $invoice = $this->invoiceValues($data, $calculated);
            $invoice['invoice_number'] = $this->nextNumber((int) substr($data['invoice_date'], 0, 4));
            $id = $this->invoices->create($invoice);
            $this->items->replaceForInvoice($id, $calculated['items']);
            $this->pdo->commit();
            return $id;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        }
    }
    public function update(int $invoiceId, array $data): void {
        $calculated = $this->calculate($data);
        $this->pdo->beginTransaction();
        try {
            $existing = $this->invoices->findForUpdate($invoiceId);
            if ($existing === null) throw new DomainException('Invoice not found.');
            $paid = $this->toPaise($this->payments->totalForInvoice($invoiceId));
            if ($calculated['total_paise'] < $paid) {
                throw new DomainException('Invoice total cannot be less than payments already received.');
            }
            $this->invoices->update($invoiceId, $this->invoiceValues($data, $calculated));
            $this->items->replaceForInvoice($invoiceId, $calculated['items']);
            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        }
    }
    public function delete(int $invoiceId): void {
        $this->pdo->beginTransaction();
        try {
            if ($this->invoices->findForUpdate($invoiceId) === null) {
                throw new DomainException('Invoice not found.');
            }
            if ($this->payments->existsForInvoice($invoiceId)) {
                throw new DomainException('This invoice has payments.');
            }
            $this->invoices->delete($invoiceId);
            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        }
    }
    private function calculate(array $data): array {
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
                'description' => $item['description'],
                'quantity' => $this->decimal($quantity, 3),
                'rate' => $this->money($rate),
                'line_total' => $this->money($lineTotal),
            ];
        }
        $cgst = $this->tax($subtotal, $this->scaled($data['cgst_rate'], 2));
        $sgst = $this->tax($subtotal, $this->scaled($data['sgst_rate'], 2));
        if ($cgst > self::MAX_AMOUNT_PAISE - $subtotal
            || $sgst > self::MAX_AMOUNT_PAISE - $subtotal - $cgst) {
            throw new InvalidArgumentException('Invoice total exceeds the allowed amount.');
        }
        return [
            'items' => $items, 'subtotal' => $subtotal, 'cgst' => $cgst, 'sgst' => $sgst,
            'total_paise' => $subtotal + $cgst + $sgst,
        ];
    }
    private function invoiceValues(array $data, array $totals): array {
        return [
            'company_id' => (int) $data['company_id'], 'invoice_date' => $data['invoice_date'],
            'subtotal' => $this->money($totals['subtotal']),
            'cgst_rate' => $this->rate($data['cgst_rate']), 'sgst_rate' => $this->rate($data['sgst_rate']),
            'cgst' => $this->money($totals['cgst']), 'sgst' => $this->money($totals['sgst']),
            'total_amount' => $this->money($totals['total_paise']),
        ];
    }
    private function lockNumberSequence(): void {
        $statement = $this->pdo->query('SELECT company_id FROM companies ORDER BY company_id LIMIT 1 FOR UPDATE');
        if ($statement->fetchColumn() === false) throw new DomainException('Create a company before creating an invoice.');
    }
    private function nextNumber(int $year): string {
        $largest = 0;
        foreach ($this->invoices->numbersForYear($year) as $number) {
            if (preg_match('/^INV-' . $year . '-([0-9]+)$/', (string) $number, $parts)) {
                $largest = max($largest, (int) $parts[1]);
            }
        }
        return sprintf('INV-%d-%04d', $year, $largest + 1);
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
    private function decimal(int $value, int $places): string {
        $unit = 10 ** $places;
        return intdiv($value, $unit) . '.' . str_pad((string) ($value % $unit), $places, '0', STR_PAD_LEFT);
    }
}
