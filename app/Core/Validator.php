<?php
declare(strict_types=1);

namespace App\Core;

final class Validator
{
    public static function company(array $input, callable $gstinExists): array
    {
        $values = [];
        foreach (['company_name', 'gstin', 'phone', 'email', 'address'] as $field) {
            $values[$field] = trim((string) ($input[$field] ?? ''));
        }
        $values['gstin'] = strtoupper($values['gstin']);
        $errors = [];
        $nameLength = self::length($values['company_name']);
        if ($nameLength < 2 || $nameLength > 150) {
            $errors['company_name'] = 'Company name must be 2 to 150 characters.';
        }
        if ($values['gstin'] !== '') {
            if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $values['gstin'])) {
                $errors['gstin'] = 'Enter a valid 15-character GSTIN.';
            } elseif ($gstinExists($values['gstin'])) {
                $errors['gstin'] = 'This GSTIN is already registered.';
            }
        }
        if ($values['phone'] !== '' && !preg_match('/^[0-9]{10}$/', $values['phone'])) {
            $errors['phone'] = 'Phone must contain exactly 10 digits.';
        }
        if ($values['email'] !== '' && (
            filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false
            || self::length($values['email']) > 254
        )) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (self::length($values['address']) > 500) {
            $errors['address'] = 'Address must be no more than 500 characters.';
        }
        return ['values' => $values, 'errors' => $errors];
    }

    private static function length(string $value): int
    {
        preg_match_all('/./us', $value, $characters);
        return count($characters[0]);
    }
}
