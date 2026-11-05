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
