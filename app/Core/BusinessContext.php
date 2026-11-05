<?php
declare(strict_types=1);

namespace App\Core;

final class BusinessContext
{
    public static function id(): int
    {
        return (int) ($_SESSION['active_business_id'] ?? 0);
    }

    public static function set(int $businessId, string $businessName): void
    {
        $_SESSION['active_business_id'] = $businessId;
        $_SESSION['active_business_name'] = $businessName;
    }

    public static function clear(): void
    {
        unset($_SESSION['active_business_id'], $_SESSION['active_business_name']);
    }

    public static function name(): string
    {
        return (string) ($_SESSION['active_business_name'] ?? '');
    }

    public static function refreshList(array $businesses): void
    {
        $_SESSION['owned_businesses'] = array_map(
            static fn (array $business): array => [
                'business_id' => (int) $business['business_id'],
                'business_name' => (string) $business['business_name'],
            ],
            $businesses
        );
    }

    public static function list(): array
    {
        $businesses = $_SESSION['owned_businesses'] ?? [];
        return is_array($businesses) ? $businesses : [];
    }
}
