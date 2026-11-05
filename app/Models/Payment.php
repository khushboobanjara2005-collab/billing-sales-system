<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class Payment
{
    public function __construct(private PDO $pdo)
    {
    }

    public function totalForInvoice(int $invoiceId): string
    {
        $statement = $this->pdo->prepare(
            'SELECT COALESCE(SUM(amount), 0.00) FROM payments WHERE invoice_id = :invoice_id'
        );
        $statement->execute(['invoice_id' => $invoiceId]);
        return (string) $statement->fetchColumn();
    }

    public function create(
        int $invoiceId,
        string $paymentDate,
        string $amount,
        string $paymentMethod
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO payments (invoice_id, payment_date, amount, payment_method)
             VALUES (:invoice_id, :payment_date, :amount, :payment_method)'
        );
        $statement->execute([
            'invoice_id' => $invoiceId,
            'payment_date' => $paymentDate,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
