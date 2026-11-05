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

    public static function invoice(array $input): array
    {
        $values = [
            'company_id' => trim(self::inputString($input['company_id'] ?? '')),
            'invoice_date' => trim(self::inputString($input['invoice_date'] ?? '')),
            'cgst_rate' => trim(self::inputString($input['cgst_rate'] ?? '2.50')),
            'sgst_rate' => trim(self::inputString($input['sgst_rate'] ?? '2.50')),
            'items' => [],
        ];
        $errors = [];
        if (filter_var($values['company_id'], FILTER_VALIDATE_INT) === false
            || (int) $values['company_id'] < 1) $errors['company_id'] = 'Choose a company.';
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $values['invoice_date']);
        if (!$date || $date->format('Y-m-d') !== $values['invoice_date']
            || $values['invoice_date'] > date('Y-m-d')) $errors['invoice_date'] = 'Enter a valid date that is not in the future.';
        foreach (['cgst_rate', 'sgst_rate'] as $field) {
            if (!preg_match('/^(?:0|[1-9][0-9]{0,2})(?:\.[0-9]{1,2})?$/', $values[$field])) {
                $errors[$field] = 'Enter a rate from 0 to 999.99.';
            }
        }
        $items = is_array($input['items'] ?? null) ? array_values($input['items']) : [];
        if ($items === [] || count($items) > 100) $errors['items'] = 'Add between 1 and 100 invoice items.';
        foreach ($items as $index => $item) {
            if (!is_array($item)) { $errors['items.' . $index] = 'Invalid invoice item.'; continue; }
            $entry = [
                'description' => trim(self::inputString($item['description'] ?? '')),
                'quantity' => trim(self::inputString($item['quantity'] ?? '')),
                'rate' => trim(self::inputString($item['rate'] ?? '')),
            ];
            if ($entry['description'] === '' || self::length($entry['description']) > 255) {
                $errors['items.' . $index . '.description'] = 'Description is required (255 characters max).';
            }
            if (!preg_match('/^(?:0|[1-9][0-9]{0,6})(?:\.[0-9]{1,3})?$/', $entry['quantity'])
                || !preg_match('/[1-9]/', $entry['quantity'])) {
                $errors['items.' . $index . '.quantity'] = 'Quantity must be greater than zero (up to 3 decimals).';
            }
            if (!preg_match('/^(?:0|[1-9][0-9]{0,9})(?:\.[0-9]{1,2})?$/', $entry['rate'])) {
                $errors['items.' . $index . '.rate'] = 'Rate must be zero or more (up to 2 decimals).';
            }
            $values['items'][] = $entry;
        }
        return ['values' => $values, 'errors' => $errors];
    }

    private static function length(string $value): int
    {
        $count = preg_match_all('/./us', $value, $characters);
        return $count === false ? PHP_INT_MAX : $count;
    }

    private static function inputString(mixed $value): string
    {
        return is_string($value) || is_int($value) ? (string) $value : '';
    }
}
