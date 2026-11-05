<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class Invoice
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findForUpdate(int $invoiceId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT invoice_id, total_amount FROM invoices WHERE invoice_id = :invoice_id FOR UPDATE'
        );
        $statement->execute(['invoice_id' => $invoiceId]);
        $invoice = $statement->fetch();
        return is_array($invoice) ? $invoice : null;
    }
}
