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
use UnexpectedValueException;

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
        int $invoiceId,
        string $paymentDate,
        string $amount,
        string $paymentMethod
    ): int {
        if ($invoiceId < 1 || !$this->isValidDate($paymentDate)) {
            throw new InvalidArgumentException('A valid invoice and payment date are required.');
        }
        $paymentMethod = trim($paymentMethod);
        if ($paymentMethod === '' || strlen($paymentMethod) > 50) {
            throw new InvalidArgumentException('A payment method of up to 50 characters is required.');
        }

        $paymentPaise = $this->toPaise($amount);
        if ($paymentPaise < 1) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $this->pdo->beginTransaction();
        try {
            $invoice = $this->invoices->findForUpdate($invoiceId);
            if ($invoice === null) {
                throw new DomainException('Invoice not found.');
            }

            $totalPaise = $this->toPaise((string) $invoice['total_amount']);
            $paidPaise = $this->toPaise($this->payments->totalForInvoice($invoiceId));
            if ($paymentPaise > $totalPaise - $paidPaise) {
                throw new DomainException('Payment exceeds the outstanding balance.');
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
