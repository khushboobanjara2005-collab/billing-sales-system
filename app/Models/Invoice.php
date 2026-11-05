<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class Invoice
{
    public function __construct(private PDO $pdo) {}

    public function findForUpdate(int $invoiceId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT invoice_id, total_amount FROM invoices WHERE invoice_id = :invoice_id FOR UPDATE'
        );
        $statement->execute(['invoice_id' => $invoiceId]);
        $invoice = $statement->fetch();
        return is_array($invoice) ? $invoice : null;
    }

    public function list(string $number, int $companyId, string $from, string $to, string $status, int $limit, int $offset): array
    {
        [$where, $parameters] = $this->filters($number, $companyId, $from, $to);
        if ($status !== '') {
            $where .= ($where === '' ? ' WHERE ' : ' AND ') . $this->statusExpression() . ' = :status';
            $parameters['status'] = $status;
        }
        $base = ' FROM invoices i JOIN companies c ON c.company_id = i.company_id
                  LEFT JOIN (SELECT invoice_id, SUM(amount) paid_amount FROM payments GROUP BY invoice_id) p
                  ON p.invoice_id = i.invoice_id ' . $where;
        $count = $this->pdo->prepare('SELECT COUNT(*)' . $base);
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();
        $sql = 'SELECT i.invoice_id, i.invoice_number, i.invoice_date, i.total_amount,
                    c.company_name, COALESCE(p.paid_amount, 0.00) AS paid_amount,
                    ' . $this->statusExpression() . ' AS payment_status' . $base .
                ' ORDER BY i.invoice_date DESC, i.invoice_id DESC LIMIT :limit OFFSET :offset';
        $statement = $this->pdo->prepare($sql);
        foreach ($parameters as $key => $value) $statement->bindValue(':' . $key, $value);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return ['rows' => $statement->fetchAll(), 'total' => $total];
    }

    public function findDetails(int $invoiceId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT i.*, c.company_name, c.gstin, c.phone, c.email, c.address,
                    COALESCE(p.paid_amount, 0.00) AS paid_amount,
                    i.total_amount - COALESCE(p.paid_amount, 0.00) AS pending_amount,
                    ' . $this->statusExpression() . ' AS payment_status
             FROM invoices i JOIN companies c ON c.company_id = i.company_id
             LEFT JOIN (SELECT invoice_id, SUM(amount) paid_amount FROM payments GROUP BY invoice_id) p
             ON p.invoice_id = i.invoice_id WHERE i.invoice_id = :id'
        );
        $statement->execute(['id' => $invoiceId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function create(array $invoice): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO invoices
             (invoice_number, company_id, invoice_date, subtotal, cgst_rate, sgst_rate, cgst, sgst, total_amount)
             VALUES (:invoice_number, :company_id, :invoice_date, :subtotal, :cgst_rate, :sgst_rate, :cgst, :sgst, :total_amount)'
        );
        $statement->execute($invoice);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $invoiceId, array $invoice): void
    {
        $invoice['invoice_id'] = $invoiceId;
        $statement = $this->pdo->prepare(
            'UPDATE invoices SET company_id = :company_id, invoice_date = :invoice_date,
             subtotal = :subtotal, cgst_rate = :cgst_rate, sgst_rate = :sgst_rate,
             cgst = :cgst, sgst = :sgst, total_amount = :total_amount
             WHERE invoice_id = :invoice_id'
        );
        $statement->execute($invoice);
    }

    public function delete(int $invoiceId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM invoices WHERE invoice_id = :id');
        $statement->execute(['id' => $invoiceId]);
    }

    public function numbersForYear(int $year): array
    {
        $statement = $this->pdo->prepare(
            'SELECT invoice_number FROM invoices WHERE invoice_number LIKE :prefix FOR UPDATE'
        );
        $statement->execute(['prefix' => 'INV-' . $year . '-%']);
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    private function filters(string $number, int $companyId, string $from, string $to): array
    {
        $where = [];
        $parameters = [];
        if ($number !== '') { $where[] = 'i.invoice_number LIKE :number'; $parameters['number'] = '%' . $number . '%'; }
        if ($companyId > 0) { $where[] = 'i.company_id = :company_id'; $parameters['company_id'] = $companyId; }
        if ($from !== '') { $where[] = 'i.invoice_date >= :date_from'; $parameters['date_from'] = $from; }
        if ($to !== '') { $where[] = 'i.invoice_date <= :date_to'; $parameters['date_to'] = $to; }
        return [$where === [] ? '' : ' WHERE ' . implode(' AND ', $where), $parameters];
    }

    private function statusExpression(): string
    {
        return "CASE WHEN COALESCE(p.paid_amount, 0) <= 0 THEN 'Unpaid'
                WHEN COALESCE(p.paid_amount, 0) >= i.total_amount THEN 'Paid'
                ELSE 'Partial' END";
    }
}
