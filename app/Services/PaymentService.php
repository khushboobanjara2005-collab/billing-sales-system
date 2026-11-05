<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use PDO;
use Throwable;

final class PaymentService
{
    private Invoice $invoices;
    private Payment $payments;

    public function __construct(private PDO $pdo)
    {
        $this->invoices = new Invoice($pdo);
        $this->payments = new Payment($pdo);
    }

    public function record(
        int $businessId,
        int $invoiceId,
        string $paymentDate,
        string $amount,
        string $paymentMethod
    ): int {
        if ($invoiceId < 1 || !$this->isValidDate($paymentDate) || $paymentDate > date('Y-m-d')) {
            throw new InvalidArgumentException('Choose an invoice and a valid payment date that is not in the future.');
        }
        if (!in_array($paymentMethod, ['Cash', 'UPI', 'Bank Transfer', 'Cheque'], true)) {
            throw new InvalidArgumentException('Choose a valid payment method.');
        }

        $paymentPaise = $this->toPaise($amount);
        if ($paymentPaise < 1) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $this->pdo->beginTransaction();
        try {
            $invoice = $this->invoices->findForUpdate($businessId, $invoiceId);
            if ($invoice === null) {
                throw new DomainException('Invoice not found.');
            }

            $totalPaise = $this->toPaise((string) $invoice['total_amount']);
            $paidPaise = $this->toPaise($this->payments->totalForInvoice($invoiceId));
            if ($paymentPaise > $totalPaise - $paidPaise) {
                throw new DomainException('Payment exceeds the outstanding balance of ₹' . $this->fromPaise(max(0, $totalPaise - $paidPaise)) . '.');
            }

            $paymentId = $this->payments->create(
                $invoiceId,
                $paymentDate,
                $this->fromPaise($paymentPaise),
                $paymentMethod
            );
            $this->pdo->commit();
            return $paymentId;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function delete(int $businessId, int $paymentId): void
    {
        $payment = $this->payments->findDetails($businessId, $paymentId);
        if ($payment === null) {
            throw new DomainException('Payment not found.');
        }

        $this->pdo->beginTransaction();
        try {
            if ($this->invoices->findForUpdate($businessId, (int) $payment['invoice_id']) === null) {
                throw new DomainException('Invoice not found.');
            }
            $this->payments->delete($businessId, $paymentId);
            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function toPaise(string $amount): int
    {
        if (!preg_match('/^(0|[1-9][0-9]{0,9})(?:\.([0-9]{1,2}))?$/', $amount, $parts)) {
            throw new InvalidArgumentException('Amount must be a non-negative decimal with up to two places.');
        }

        $rupees = (int) $parts[1];
        $paise = isset($parts[2]) ? (int) str_pad($parts[2], 2, '0') : 0;
        return ($rupees * 100) + $paise;
    }

    private function fromPaise(int $amount): string
    {
        return intdiv($amount, 100) . '.' . str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
