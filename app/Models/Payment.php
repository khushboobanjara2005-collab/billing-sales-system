<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class Payment
{
    public function __construct(private PDO $pdo) {}

    public function paginate(int $companyId, string $method, string $from, string $to, int $limit, int $offset): array
    {
        [$where, $parameters] = $this->filters($companyId, $method, $from, $to);
        $count = $this->pdo->prepare(
            'SELECT COUNT(*) FROM payments p JOIN invoices i ON i.invoice_id = p.invoice_id ' . $where
        );
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();
        $statement = $this->pdo->prepare(
            'SELECT p.payment_id, p.payment_date, p.amount, p.payment_method,
                    i.invoice_id, i.invoice_number, c.company_name
             FROM payments p JOIN invoices i ON i.invoice_id = p.invoice_id
             JOIN companies c ON c.company_id = i.company_id ' . $where .
            ' ORDER BY p.payment_date DESC, p.payment_id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($parameters as $key => $value) $statement->bindValue(':' . $key, $value);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return ['rows' => $statement->fetchAll(), 'total' => $total];
    }

    public function findDetails(int $paymentId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.*, i.invoice_number, i.invoice_id, c.company_name
             FROM payments p JOIN invoices i ON i.invoice_id = p.invoice_id
             JOIN companies c ON c.company_id = i.company_id WHERE p.payment_id = :id'
        );
        $statement->execute(['id' => $paymentId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function invoicesForSelect(): array
    {
        return $this->pdo->query(
            'SELECT i.invoice_id, i.invoice_number, c.company_name, i.total_amount,
                    COALESCE(p.paid_amount, 0.00) AS paid_amount,
                    i.total_amount - COALESCE(p.paid_amount, 0.00) AS pending_amount
             FROM invoices i JOIN companies c ON c.company_id = i.company_id
             LEFT JOIN (SELECT invoice_id, SUM(amount) paid_amount FROM payments GROUP BY invoice_id) p
             ON p.invoice_id = i.invoice_id ORDER BY i.invoice_date DESC, i.invoice_id DESC'
        )->fetchAll();
    }

    public function delete(int $paymentId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM payments WHERE payment_id = :id');
        $statement->execute(['id' => $paymentId]);
    }

    public function totalForInvoice(int $invoiceId): string
    {
        $statement = $this->pdo->prepare(
            'SELECT COALESCE(SUM(amount), 0.00) FROM payments WHERE invoice_id = :invoice_id'
        );
        $statement->execute(['invoice_id' => $invoiceId]);
        return (string) $statement->fetchColumn();
    }

    public function existsForInvoice(int $invoiceId): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM payments WHERE invoice_id = :invoice_id LIMIT 1');
        $statement->execute(['invoice_id' => $invoiceId]);
        return $statement->fetchColumn() !== false;
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

    private function filters(int $companyId, string $method, string $from, string $to): array
    {
        $conditions = [];
        $parameters = [];
        if ($companyId > 0) { $conditions[] = 'i.company_id = :company_id'; $parameters['company_id'] = $companyId; }
        if ($method !== '') { $conditions[] = 'p.payment_method = :method'; $parameters['method'] = $method; }
        if ($from !== '') { $conditions[] = 'p.payment_date >= :date_from'; $parameters['date_from'] = $from; }
        if ($to !== '') { $conditions[] = 'p.payment_date <= :date_to'; $parameters['date_to'] = $to; }
        return [$conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions), $parameters];
    }
}
