<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class SellerProfile
{
    public function __construct(private PDO $pdo) {}

    public function find(): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM seller_profile WHERE profile_id = 1');
        $statement->execute();
        return $statement->fetch() ?: [];
    }

    public function save(array $profile): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE seller_profile SET seller_name = :seller_name,
             business_description = :business_description, address = :address, phone = :phone,
             gstin = :gstin, bank_name = :bank_name, branch = :branch,
             account_number = :account_number, ifsc_code = :ifsc_code, terms = :terms
             WHERE profile_id = 1'
        );
        $statement->execute($profile);
        if ($statement->rowCount() === 0 && $this->find() === []) {
            $profile['profile_id'] = 1;
            $insert = $this->pdo->prepare(
                'INSERT INTO seller_profile
                 (profile_id, seller_name, business_description, address, phone, gstin,
                  bank_name, branch, account_number, ifsc_code, terms)
                 VALUES (:profile_id, :seller_name, :business_description, :address, :phone, :gstin,
                         :bank_name, :branch, :account_number, :ifsc_code, :terms)'
            );
            $insert->execute($profile);
        }
    }
}
