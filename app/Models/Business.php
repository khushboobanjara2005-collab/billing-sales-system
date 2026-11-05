<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class Business
{
    private const FIELDS = [
        'business_name', 'tagline', 'business_type', 'owner_name', 'address', 'city', 'state',
        'pincode', 'phone', 'email', 'gstin', 'state_code', 'bank_name', 'branch_name',
        'account_holder_name', 'account_number', 'ifsc_code', 'invoice_prefix',
        'next_invoice_number', 'default_gst_rate', 'jurisdiction', 'authorized_signatory',
        'terms_and_conditions',
    ];

    public function __construct(private PDO $pdo) {}

    public function allOwned(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT business_id, business_name, jurisdiction FROM businesses
             WHERE owner_user_id = :user_id ORDER BY business_name, business_id'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function findOwned(int $businessId, int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM businesses WHERE business_id = :business_id AND owner_user_id = :user_id'
        );
        $statement->execute(['business_id' => $businessId, 'user_id' => $userId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function findForUpdate(int $businessId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT business_id, invoice_prefix, next_invoice_number
             FROM businesses WHERE business_id = :business_id FOR UPDATE'
        );
        $statement->execute(['business_id' => $businessId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function findById(int $businessId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM businesses WHERE business_id = :business_id'
        );
        $statement->execute(['business_id' => $businessId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function advanceInvoiceNumber(int $businessId, int $nextNumber): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE businesses SET next_invoice_number = :next_number WHERE business_id = :business_id'
        );
        $statement->execute(['next_number' => $nextNumber, 'business_id' => $businessId]);
    }

    public function create(int $userId, array $values): int
    {
        $columns = implode(', ', self::FIELDS);
        $placeholders = implode(', ', array_map(static fn (string $field): string => ':' . $field, self::FIELDS));
        $statement = $this->pdo->prepare(
            "INSERT INTO businesses (owner_user_id, {$columns}) VALUES (:owner_user_id, {$placeholders})"
        );
        $statement->execute($this->values($values) + ['owner_user_id' => $userId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateOwned(int $businessId, int $userId, array $values): bool
    {
        $editable = array_values(array_filter(self::FIELDS, static fn (string $field): bool => $field !== 'next_invoice_number'));
        $assignments = implode(', ', array_map(static fn (string $field): string => $field . ' = :' . $field, $editable));
        $assignments .= ', next_invoice_number = GREATEST(next_invoice_number, :next_invoice_number)';
        $parameters = $this->values($values);
        $parameters['business_id'] = $businessId;
        $parameters['user_id'] = $userId;
        $statement = $this->pdo->prepare(
            "UPDATE businesses SET {$assignments} WHERE business_id = :business_id AND owner_user_id = :user_id"
        );
        $statement->execute($parameters);
        return $statement->rowCount() > 0 || $this->findOwned($businessId, $userId) !== null;
    }

    public function claimLegacyData(int $userId): ?int
    {
        $state = $this->pdo->query(
            'SELECT legacy_data_claimed FROM installation_state WHERE state_id = 1 FOR UPDATE'
        )->fetchColumn();
        if ((int) $state !== 0) {
            return null;
        }

        $profile = $this->pdo->query('SELECT * FROM seller_profile WHERE profile_id = 1')->fetch() ?: [];
        $values = [
            'business_name' => $profile['seller_name'] ?? 'My Business',
            'tagline' => $profile['business_description'] ?? '',
            'business_type' => '',
            'owner_name' => '',
            'address' => $profile['address'] ?? '',
            'city' => '',
            'state' => '',
            'pincode' => '',
            'phone' => $profile['phone'] ?? '',
            'email' => '',
            'gstin' => $profile['gstin'] ?? '',
            'state_code' => preg_match('/^[0-9]{2}/', (string) ($profile['gstin'] ?? ''), $match) ? $match[0] : '',
            'bank_name' => $profile['bank_name'] ?? '',
            'branch_name' => $profile['branch'] ?? '',
            'account_holder_name' => $profile['seller_name'] ?? '',
            'account_number' => $profile['account_number'] ?? '',
            'ifsc_code' => $profile['ifsc_code'] ?? '',
            'invoice_prefix' => 'INV',
            'next_invoice_number' => 1,
            'default_gst_rate' => '2.50',
            'jurisdiction' => '',
            'authorized_signatory' => '',
            'terms_and_conditions' => $profile['terms'] ?? '',
        ];
        $businessId = $this->create($userId, $values);
        $companies = $this->pdo->prepare(
            'UPDATE companies SET business_id = :business_id WHERE business_id IS NULL'
        );
        $companies->execute(['business_id' => $businessId]);
        $invoices = $this->pdo->prepare(
            'UPDATE invoices SET business_id = :business_id WHERE business_id IS NULL'
        );
        $invoices->execute(['business_id' => $businessId]);
        $this->pdo->prepare(
            'UPDATE installation_state SET legacy_data_claimed = 1 WHERE state_id = 1'
        )->execute();
        $numberQuery = $this->pdo->prepare(
            'SELECT invoice_number FROM invoices WHERE business_id = :business_id FOR UPDATE'
        );
        $numberQuery->execute(['business_id' => $businessId]);
        $largest = 0;
        foreach ($numberQuery->fetchAll(PDO::FETCH_COLUMN) as $number) {
            if (preg_match('/^INV-[0-9]{4}-([0-9]+)$/', (string) $number, $parts)) {
                $largest = max($largest, (int) $parts[1]);
            }
        }
        $this->setNextInvoiceNumber($businessId, $largest + 1);
        return $businessId;
    }

    private function setNextInvoiceNumber(int $businessId, int $next): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE businesses SET next_invoice_number = :next_number WHERE business_id = :business_id'
        );
        $statement->execute(['next_number' => $next, 'business_id' => $businessId]);
    }

    private function values(array $values): array
    {
        $result = [];
        foreach (self::FIELDS as $field) {
            $result[$field] = $values[$field] ?? null;
        }
        return $result;
    }
}
