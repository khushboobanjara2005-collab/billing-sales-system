<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class InvoiceItem
{
    public function __construct(private PDO $pdo)
    {
    }

    public function forInvoice(int $invoiceId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT item_id, description, quantity, rate, line_total
             FROM invoice_items
             WHERE invoice_id = :invoice_id
             ORDER BY item_id'
        );
        $statement->execute(['invoice_id' => $invoiceId]);
        return $statement->fetchAll();
    }
}
