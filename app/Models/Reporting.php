<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class Reporting
{
    public function __construct(private PDO $pdo) {}

    public function dashboard(string $monthStart): array
    {
        $summary = $this->pdo->prepare(
            'SELECT (SELECT COALESCE(SUM(total_amount), 0) FROM invoices) AS sales,
                    (SELECT COALESCE(SUM(amount), 0) FROM payments) AS received,
                    (SELECT COUNT(*) FROM companies) AS companies,
                    (SELECT COUNT(*) FROM invoices) AS invoices'
        );
        $summary->execute();
        $totals = $summary->fetch();
        $totals['pending'] = $this->pendingTotal();

        $recent = $this->pdo->prepare(
            'SELECT i.invoice_id, i.invoice_number, i.invoice_date, i.total_amount,
                    c.company_name, COALESCE(p.paid, 0) AS paid_amount
             FROM invoices i JOIN companies c ON c.company_id = i.company_id
             LEFT JOIN (SELECT invoice_id, SUM(amount) paid FROM payments GROUP BY invoice_id) p
               ON p.invoice_id = i.invoice_id
             ORDER BY i.invoice_date DESC, i.invoice_id DESC LIMIT 5'
        );
        $recent->execute();
        $monthly = $this->pdo->prepare(
            'SELECT DATE_FORMAT(invoice_date, "%Y-%m") AS month_key, SUM(total_amount) AS sales
             FROM invoices WHERE invoice_date >= :month_start
             GROUP BY DATE_FORMAT(invoice_date, "%Y-%m") ORDER BY month_key'
        );
        $monthly->execute(['month_start' => $monthStart]);
        return ['totals' => $totals, 'recent' => $recent->fetchAll(), 'monthly' => $monthly->fetchAll()];
    }

    public function salesSummary(string $from, string $to): array
    {
        $where = $this->dateWhere('i.invoice_date', $from, $to, $parameters);
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

    public function companyOutstanding(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT c.company_id, c.company_name, COALESCE(SUM(i.total_amount), 0) AS sales,
                    COALESCE(SUM(p.paid), 0) AS received,
                    COALESCE(SUM(i.total_amount - COALESCE(p.paid, 0)), 0) AS pending
             FROM companies c LEFT JOIN invoices i ON i.company_id = c.company_id
             LEFT JOIN (SELECT invoice_id, SUM(amount) paid FROM payments GROUP BY invoice_id) p
               ON p.invoice_id = i.invoice_id
             GROUP BY c.company_id, c.company_name
             HAVING pending > 0 ORDER BY pending DESC, c.company_name'
        );
        $statement->execute();
        return $statement->fetchAll();
    }

    public function paymentMethods(string $from, string $to): array
    {
        $where = $this->dateWhere('payment_date', $from, $to, $parameters);
        $statement = $this->pdo->prepare(
            'SELECT payment_method, COUNT(*) AS payment_count, SUM(amount) AS total
             FROM payments ' . $where . ' GROUP BY payment_method ORDER BY total DESC, payment_method'
        );
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    private function pendingTotal(): string
    {
        $statement = $this->pdo->prepare(
            'SELECT COALESCE(SUM(i.total_amount - COALESCE(p.paid, 0)), 0)
             FROM invoices i LEFT JOIN
                (SELECT invoice_id, SUM(amount) paid FROM payments GROUP BY invoice_id) p
                ON p.invoice_id = i.invoice_id'
        );
        $statement->execute();
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
