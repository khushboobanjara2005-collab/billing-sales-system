<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class InvoiceItem
{
    public function __construct(private PDO $pdo) {}

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

    public function replaceForInvoice(int $invoiceId, array $items): void
    {
        $delete = $this->pdo->prepare('DELETE FROM invoice_items WHERE invoice_id = :invoice_id');
        $delete->execute(['invoice_id' => $invoiceId]);
        $insert = $this->pdo->prepare(
            'INSERT INTO invoice_items (invoice_id, description, quantity, rate, line_total)
             VALUES (:invoice_id, :description, :quantity, :rate, :line_total)'
        );
        foreach ($items as $item) {
            $insert->execute([
                'invoice_id' => $invoiceId, 'description' => $item['description'],
                'quantity' => $item['quantity'], 'rate' => $item['rate'], 'line_total' => $item['line_total'],
            ]);
        }
    }
}
