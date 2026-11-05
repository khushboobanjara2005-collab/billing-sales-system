<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class Reporting
{
    public function __construct(private PDO $pdo) {}

    public function dashboard(int $businessId, string $monthStart): array
    {
        $summary = $this->pdo->prepare(
            'SELECT (SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE business_id = :business_sales) AS sales,
                    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p JOIN invoices i ON i.invoice_id = p.invoice_id
                     WHERE i.business_id = :business_received) AS received,
                    (SELECT COUNT(*) FROM companies WHERE business_id = :business_companies) AS companies,
                    (SELECT COUNT(*) FROM invoices WHERE business_id = :business_invoices) AS invoices'
        );
        $summary->execute([
            'business_sales' => $businessId, 'business_received' => $businessId,
            'business_companies' => $businessId, 'business_invoices' => $businessId,
        ]);
        $totals = $summary->fetch();
        $totals['pending'] = $this->pendingTotal($businessId);

        $recent = $this->pdo->prepare(
            'SELECT i.invoice_id, i.invoice_number, i.invoice_date, i.total_amount,
                    c.company_name, COALESCE(p.paid, 0) AS paid_amount
             FROM invoices i JOIN companies c
               ON c.company_id = i.company_id AND c.business_id = i.business_id
             LEFT JOIN (SELECT invoice_id, SUM(amount) paid FROM payments GROUP BY invoice_id) p
               ON p.invoice_id = i.invoice_id
             WHERE i.business_id = :business_id
             ORDER BY i.invoice_date DESC, i.invoice_id DESC LIMIT 5'
        );
        $recent->execute(['business_id' => $businessId]);
        $monthly = $this->pdo->prepare(
            'SELECT DATE_FORMAT(invoice_date, "%Y-%m") AS month_key, SUM(total_amount) AS sales
             FROM invoices WHERE business_id = :business_id AND invoice_date >= :month_start
             GROUP BY DATE_FORMAT(invoice_date, "%Y-%m") ORDER BY month_key'
        );
        $monthly->execute(['business_id' => $businessId, 'month_start' => $monthStart]);
        return ['totals' => $totals, 'recent' => $recent->fetchAll(), 'monthly' => $monthly->fetchAll()];
    }

    public function salesSummary(int $businessId, string $from, string $to): array
    {
        $where = $this->dateWhere('i.invoice_date', $from, $to, $parameters);
        $where .= ($where === '' ? ' WHERE ' : ' AND ') . 'i.business_id = :business_id';
        $parameters['business_id'] = $businessId;
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) AS invoice_count, COALESCE(SUM(i.total_amount), 0) AS sales,
                    COALESCE(SUM(COALESCE(p.paid, 0)), 0) AS received,
                    COALESCE(SUM(i.total_amount - COALESCE(p.paid, 0)), 0) AS pending
             FROM invoices i LEFT JOIN
                (SELECT invoice_id, SUM(amount) paid FROM payments GROUP BY invoice_id) p
                ON p.invoice_id = i.invoice_id ' . $where
        );
        $statement->execute($parameters);
        return $statement->fetch();
    }

    public function companyOutstanding(int $businessId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT c.company_id, c.company_name, COALESCE(SUM(i.total_amount), 0) AS sales,
                    COALESCE(SUM(p.paid), 0) AS received,
                    COALESCE(SUM(i.total_amount - COALESCE(p.paid, 0)), 0) AS pending
             FROM companies c LEFT JOIN invoices i
               ON i.company_id = c.company_id AND i.business_id = c.business_id
             LEFT JOIN (SELECT invoice_id, SUM(amount) paid FROM payments GROUP BY invoice_id) p
               ON p.invoice_id = i.invoice_id
             WHERE c.business_id = :business_id GROUP BY c.company_id, c.company_name
             HAVING pending > 0 ORDER BY pending DESC, c.company_name'
        );
        $statement->execute(['business_id' => $businessId]);
        return $statement->fetchAll();
    }

    public function paymentMethods(int $businessId, string $from, string $to): array
    {
        $where = $this->dateWhere('p.payment_date', $from, $to, $parameters);
        $where .= ($where === '' ? ' WHERE ' : ' AND ') . 'i.business_id = :business_id';
        $parameters['business_id'] = $businessId;
        $statement = $this->pdo->prepare(
            'SELECT p.payment_method, COUNT(*) AS payment_count, SUM(p.amount) AS total
             FROM payments p JOIN invoices i ON i.invoice_id = p.invoice_id ' . $where .
            ' GROUP BY p.payment_method ORDER BY total DESC, p.payment_method'
        );
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    private function pendingTotal(int $businessId): string
    {
        $statement = $this->pdo->prepare(
            'SELECT COALESCE(SUM(i.total_amount - COALESCE(p.paid, 0)), 0)
             FROM invoices i LEFT JOIN
                (SELECT invoice_id, SUM(amount) paid FROM payments GROUP BY invoice_id) p
                ON p.invoice_id = i.invoice_id WHERE i.business_id = :business_id'
        );
        $statement->execute(['business_id' => $businessId]);
        return (string) $statement->fetchColumn();
    }

    private function dateWhere(string $column, string $from, string $to, ?array &$parameters): string
    {
        $conditions = [];
        $parameters = [];
        if ($from !== '') { $conditions[] = $column . ' >= :date_from'; $parameters['date_from'] = $from; }
        if ($to !== '') { $conditions[] = $column . ' <= :date_to'; $parameters['date_to'] = $to; }
        return $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
    }
}
