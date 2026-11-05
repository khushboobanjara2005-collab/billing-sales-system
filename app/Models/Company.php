<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class Company
{
    public function __construct(private PDO $pdo)
    {
    }

    public function paginate(string $searchTerm, int $limit, int $offset): array
    {
        $statement = $this->pdo->prepare(
            'SELECT company_id, company_name, gstin, phone, email
             FROM companies
             WHERE company_name LIKE :name OR gstin LIKE :gstin
                OR phone LIKE :phone OR email LIKE :email
             ORDER BY company_name, company_id
             LIMIT :limit OFFSET :offset'
        );
        $like = '%' . $searchTerm . '%';
        $statement->bindValue(':name', $like);
        $statement->bindValue(':gstin', $like);
        $statement->bindValue(':phone', $like);
        $statement->bindValue(':email', $like);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        $rows = $statement->fetchAll();

        $count = $this->pdo->prepare(
            'SELECT COUNT(*) FROM companies
             WHERE company_name LIKE :name OR gstin LIKE :gstin
                OR phone LIKE :phone OR email LIKE :email'
        );
        $count->execute(['name' => $like, 'gstin' => $like, 'phone' => $like, 'email' => $like]);
        return ['rows' => $rows, 'total' => (int) $count->fetchColumn()];
    }

    public function find(int $companyId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM companies WHERE company_id = :id');
        $statement->execute(['id' => $companyId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function allForSelect(): array
    {
        return $this->pdo->query(
            'SELECT company_id, company_name FROM companies ORDER BY company_name, company_id'
        )->fetchAll();
    }

    public function ledger(int $companyId, string $from, string $to, string $status): array
    {
        $conditions = ['i.company_id = :company_id'];
        $parameters = ['company_id' => $companyId];
        if ($from !== '') { $conditions[] = 'i.invoice_date >= :date_from'; $parameters['date_from'] = $from; }
        if ($to !== '') { $conditions[] = 'i.invoice_date <= :date_to'; $parameters['date_to'] = $to; }
        if ($status !== '') {
            $conditions[] = "CASE WHEN COALESCE(p.paid, 0) = 0 THEN 'Unpaid'
                WHEN p.paid >= i.total_amount THEN 'Paid' ELSE 'Partial' END = :status";
            $parameters['status'] = $status;
        }
        $sql = 'SELECT i.invoice_id, i.invoice_number, i.invoice_date, i.total_amount,
                    COALESCE(p.paid, 0.00) AS paid_amount,
                    i.total_amount - COALESCE(p.paid, 0.00) AS pending_amount,
                    CASE WHEN COALESCE(p.paid, 0) = 0 THEN "Unpaid"
                         WHEN p.paid >= i.total_amount THEN "Paid" ELSE "Partial" END AS payment_status
                FROM invoices i LEFT JOIN
                    (SELECT invoice_id, SUM(amount) paid FROM payments GROUP BY invoice_id) p
                    ON p.invoice_id = i.invoice_id WHERE ' . implode(' AND ', $conditions) .
                ' ORDER BY i.invoice_date, i.invoice_id';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);
        $rows = $statement->fetchAll();
        $totals = ['total' => 0, 'paid' => 0, 'pending' => 0, 'running' => 0];
        foreach ($rows as &$row) {
            $row['total_paise'] = self::toPaise((string) $row['total_amount']);
            $row['paid_paise'] = self::toPaise((string) $row['paid_amount']);
            $row['pending_paise'] = self::toPaise((string) $row['pending_amount']);
            $totals['total'] += $row['total_paise'];
            $totals['paid'] += $row['paid_paise'];
            $totals['pending'] += $row['pending_paise'];
            $totals['running'] += $row['pending_paise'];
            $row['running_balance'] = self::fromPaise($totals['running']);
        }
        unset($row);
        return ['rows' => $rows, 'totals' => [
            'total' => self::fromPaise($totals['total']), 'paid' => self::fromPaise($totals['paid']),
            'pending' => self::fromPaise($totals['pending']),
        ]];
    }

    private static function toPaise(string $amount): int
    {
        [$rupees, $paise] = array_pad(explode('.', $amount, 2), 2, '0');
        return ((int) $rupees * 100) + (int) str_pad($paise, 2, '0');
    }

    private static function fromPaise(int $amount): string
    {
        return intdiv($amount, 100) . '.' . str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
    public function gstinExists(string $gstin, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM companies WHERE gstin = :gstin';
        if ($exceptId !== null) {
            $sql .= ' AND company_id <> :except_id';
        }
        $statement = $this->pdo->prepare($sql . ' LIMIT 1');
        $parameters = ['gstin' => $gstin];
        if ($exceptId !== null) {
            $parameters['except_id'] = $exceptId;
        }
        $statement->execute($parameters);
        return $statement->fetchColumn() !== false;
    }

    public function create(array $company): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO companies (company_name, gstin, phone, email, address)
             VALUES (:company_name, :gstin, :phone, :email, :address)'
        );
        $statement->execute($company);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $companyId, array $company): void
    {
        $company['company_id'] = $companyId;
        $statement = $this->pdo->prepare(
            'UPDATE companies SET company_name = :company_name, gstin = :gstin,
             phone = :phone, email = :email, address = :address
             WHERE company_id = :company_id'
        );
        $statement->execute($company);
    }

    public function delete(int $companyId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM companies WHERE company_id = :id');
        $statement->execute(['id' => $companyId]);
    }
}
