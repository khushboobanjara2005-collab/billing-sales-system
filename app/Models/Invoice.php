<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class Invoice
{
    public function __construct(private PDO $pdo) {}

    public function findForUpdate(int $businessId, int $invoiceId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT invoice_id, total_amount FROM invoices
             WHERE business_id = :business_id AND invoice_id = :invoice_id FOR UPDATE'
        );
        $statement->execute(['business_id' => $businessId, 'invoice_id' => $invoiceId]);
        $invoice = $statement->fetch();
        return is_array($invoice) ? $invoice : null;
    }

    public function list(int $businessId, string $number, int $companyId, string $from, string $to, string $status, int $limit, int $offset): array
    {
        [$where, $parameters] = $this->filters($businessId, $number, $companyId, $from, $to);
        if ($status !== '') {
            $where .= ($where === '' ? ' WHERE ' : ' AND ') . $this->statusExpression() . ' = :status';
            $parameters['status'] = $status;
        }
        $base = ' FROM invoices i JOIN companies c
                  ON c.company_id = i.company_id AND c.business_id = i.business_id
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

    public function findDetails(int $businessId, int $invoiceId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT i.*, c.company_name, c.gstin, c.phone, c.email, c.address,
                    COALESCE(p.paid_amount, 0.00) AS paid_amount,
                    i.total_amount - COALESCE(p.paid_amount, 0.00) AS pending_amount,
                    ' . $this->statusExpression() . ' AS payment_status
             FROM invoices i JOIN companies c
               ON c.company_id = i.company_id AND c.business_id = i.business_id
             LEFT JOIN (SELECT invoice_id, SUM(amount) paid_amount FROM payments GROUP BY invoice_id) p
             ON p.invoice_id = i.invoice_id WHERE i.business_id = :business_id AND i.invoice_id = :id'
        );
        $statement->execute(['business_id' => $businessId, 'id' => $invoiceId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function create(array $invoice): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO invoices
             (business_id, invoice_number, company_id, invoice_date, subtotal, cgst_rate, sgst_rate, tax_type,
              cgst, sgst, igst, round_off, total_amount)
             VALUES (:business_id, :invoice_number, :company_id, :invoice_date, :subtotal, :cgst_rate, :sgst_rate, :tax_type,
                     :cgst, :sgst, :igst, :round_off, :total_amount)'
        );
        $statement->execute($invoice);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $businessId, int $invoiceId, array $invoice): void
    {
        $invoice['business_id'] = $businessId;
        $invoice['invoice_id'] = $invoiceId;
        $statement = $this->pdo->prepare(
            'UPDATE invoices SET business_id = :business_id, company_id = :company_id, invoice_date = :invoice_date,
             subtotal = :subtotal, cgst_rate = :cgst_rate, sgst_rate = :sgst_rate, tax_type = :tax_type,
             cgst = :cgst, sgst = :sgst, igst = :igst, round_off = :round_off, total_amount = :total_amount
             WHERE business_id = :where_business_id AND invoice_id = :invoice_id'
        );
        $invoice['where_business_id'] = $businessId;
        $statement->execute($invoice);
    }

    public function delete(int $businessId, int $invoiceId): void
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM invoices WHERE business_id = :business_id AND invoice_id = :id'
        );
        $statement->execute(['business_id' => $businessId, 'id' => $invoiceId]);
    }

    public function numbersForYear(int $businessId, int $year): array
    {
        $statement = $this->pdo->prepare(
            'SELECT invoice_number FROM invoices WHERE business_id = :business_id
             AND invoice_number LIKE :prefix FOR UPDATE'
        );
        $statement->execute(['business_id' => $businessId, 'prefix' => 'INV-' . $year . '-%']);
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    private function filters(int $businessId, string $number, int $companyId, string $from, string $to): array
    {
        $where = ['i.business_id = :business_id'];
        $parameters = ['business_id' => $businessId];
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
