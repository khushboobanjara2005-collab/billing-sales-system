<?php
declare(strict_types=1);

namespace App\Core;

final class Validator
{
    public static function registration(array $input): array
    {
        $values = [
            'full_name' => trim(self::inputString($input['full_name'] ?? '')),
            'email' => strtolower(trim(self::inputString($input['email'] ?? ''))),
            'phone' => trim(self::inputString($input['phone'] ?? '')),
            'password' => self::inputString($input['password'] ?? ''),
            'password_confirmation' => self::inputString($input['password_confirmation'] ?? ''),
        ];
        $errors = [];
        if (self::length($values['full_name']) < 2 || self::length($values['full_name']) > 150) {
            $errors['full_name'] = 'Name must be 2 to 150 characters.';
        }
        if (filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false
            || self::length($values['email']) > 254) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if ($values['phone'] !== '' && !preg_match('/^[0-9]{10}$/', $values['phone'])) {
            $errors['phone'] = 'Phone must contain exactly 10 digits.';
        }
        if (strlen($values['password']) < 12 || strlen($values['password']) > 200) {
            $errors['password'] = 'Password must be 12 to 200 characters.';
        }
        if (!hash_equals($values['password'], $values['password_confirmation'])) {
            $errors['password_confirmation'] = 'Passwords do not match.';
        }
        return ['values' => $values, 'errors' => $errors];
    }

    public static function business(array $input): array
    {
        $lengths = [
            'business_name' => 150, 'tagline' => 255, 'business_type' => 100,
            'owner_name' => 150, 'address' => 500, 'city' => 100, 'state' => 100,
            'pincode' => 10, 'phone' => 20, 'email' => 254, 'gstin' => 15,
            'state_code' => 2, 'bank_name' => 100, 'branch_name' => 100,
            'account_holder_name' => 150, 'account_number' => 34, 'ifsc_code' => 11,
            'invoice_prefix' => 20, 'jurisdiction' => 150, 'authorized_signatory' => 150,
            'terms_and_conditions' => 4000,
        ];
        $values = [];
        $errors = [];
        foreach ($lengths as $field => $limit) {
            $values[$field] = trim(self::inputString($input[$field] ?? ''));
            if (self::length($values[$field]) > $limit) $errors[$field] = 'This field is too long.';
        }
        $values['gstin'] = strtoupper($values['gstin']);
        $values['state_code'] = $values['state_code'] === ''
            ? substr($values['gstin'], 0, 2) : $values['state_code'];
        $values['ifsc_code'] = strtoupper($values['ifsc_code']);
        $values['next_invoice_number'] = trim(self::inputString($input['next_invoice_number'] ?? '1'));
        $values['default_gst_rate'] = trim(self::inputString($input['default_gst_rate'] ?? '2.50'));
        if (self::length($values['business_name']) < 2) {
            $errors['business_name'] = 'Business name is required (2 to 150 characters).';
        }
        if ($values['jurisdiction'] === '') {
            $errors['jurisdiction'] = 'Enter the business jurisdiction for invoice terms.';
        }
        if ($values['email'] !== '' && filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if ($values['phone'] !== '' && !preg_match('/^[0-9]{10}$/', $values['phone'])) {
            $errors['phone'] = 'Phone must contain exactly 10 digits.';
        }
        if ($values['pincode'] !== '' && !preg_match('/^[0-9]{6}$/', $values['pincode'])) {
            $errors['pincode'] = 'PIN code must contain 6 digits.';
        }
        if ($values['gstin'] !== '' && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $values['gstin'])) {
            $errors['gstin'] = 'Enter a valid 15-character GSTIN.';
        }
        if ($values['state_code'] !== '' && !preg_match('/^[0-9]{2}$/', $values['state_code'])) {
            $errors['state_code'] = 'State code must contain 2 digits.';
        }
        if ($values['ifsc_code'] !== '' && !preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $values['ifsc_code'])) {
            $errors['ifsc_code'] = 'Enter a valid 11-character IFSC code.';
        }
        if ($values['account_number'] !== '' && !preg_match('/^[A-Za-z0-9-]{1,34}$/', $values['account_number'])) {
            $errors['account_number'] = 'Use letters, numbers, and hyphens only.';
        }
        if (!preg_match('/^[A-Za-z0-9-]{1,20}$/', $values['invoice_prefix'])) {
            $errors['invoice_prefix'] = 'Use 1 to 20 letters, numbers, or hyphens.';
        }
        if (!preg_match('/^[1-9][0-9]{0,8}$/', $values['next_invoice_number'])) {
            $errors['next_invoice_number'] = 'Starting number must be a positive whole number.';
        }
        if (!preg_match('/^(?:0|[1-9][0-9]?)(?:\.[0-9]{1,2})?$/', $values['default_gst_rate'])) {
            $errors['default_gst_rate'] = 'Enter a GST rate from 0 to 99.99.';
        }
        return ['values' => $values, 'errors' => $errors];
    }

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
            'tax_type' => trim(self::inputString($input['tax_type'] ?? 'intra')),
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
        if (!in_array($values['tax_type'], ['intra', 'inter'], true)) {
            $errors['tax_type'] = 'Choose intra-state or inter-state GST.';
        }
        $items = is_array($input['items'] ?? null) ? array_values($input['items']) : [];
        if ($items === [] || count($items) > 100) $errors['items'] = 'Add between 1 and 100 invoice items.';
        foreach ($items as $index => $item) {
            if (!is_array($item)) { $errors['items.' . $index] = 'Invalid invoice item.'; continue; }
            $entry = [
                'weigh_bridge_serial' => trim(self::inputString($item['weigh_bridge_serial'] ?? '')),
                'item_date' => trim(self::inputString($item['item_date'] ?? $values['invoice_date'])),
                'description' => trim(self::inputString($item['description'] ?? '')),
                'quantity' => trim(self::inputString($item['quantity'] ?? '')),
                'rate' => trim(self::inputString($item['rate'] ?? '')),
            ];
            if ($entry['weigh_bridge_serial'] !== ''
                && !preg_match('/^[A-Za-z0-9][A-Za-z0-9 .\/_-]{0,49}$/', $entry['weigh_bridge_serial'])) {
                $errors['items.' . $index . '.weigh_bridge_serial'] = 'Use up to 50 letters, numbers, spaces, dots, slashes, underscores, or hyphens.';
            }
            $itemDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $entry['item_date']);
            if (!$itemDate || $itemDate->format('Y-m-d') !== $entry['item_date']
                || $entry['item_date'] > date('Y-m-d')) {
                $errors['items.' . $index . '.item_date'] = 'Enter a valid item date that is not in the future.';
            }
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

    public static function sellerProfile(array $input): array
    {
        $fields = [
            'seller_name' => 150, 'business_description' => 255, 'address' => 500,
            'phone' => 30, 'gstin' => 15, 'bank_name' => 100, 'branch' => 100,
            'account_number' => 34, 'ifsc_code' => 11, 'terms' => 4000,
        ];
        $values = [];
        $errors = [];
        foreach ($fields as $field => $maxLength) {
            $values[$field] = trim(self::inputString($input[$field] ?? ''));
            if (self::length($values[$field]) > $maxLength) {
                $errors[$field] = 'This field is too long.';
            }
        }
        if (self::length($values['seller_name']) < 2) {
            $errors['seller_name'] = 'Enter a seller name.';
        }
        if ($values['phone'] !== '' && !preg_match('/^[0-9]{10}$/', $values['phone'])) {
            $errors['phone'] = 'Enter a 10-digit phone number.';
        }
        if ($values['gstin'] !== '' && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', strtoupper($values['gstin']))) {
            $errors['gstin'] = 'Enter a valid 15-character GSTIN.';
        } else {
            $values['gstin'] = strtoupper($values['gstin']);
        }
        if ($values['ifsc_code'] !== '' && !preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', strtoupper($values['ifsc_code']))) {
            $errors['ifsc_code'] = 'Enter a valid 11-character IFSC code.';
        } else {
            $values['ifsc_code'] = strtoupper($values['ifsc_code']);
        }
        if ($values['account_number'] !== '' && !preg_match('/^[A-Za-z0-9-]{1,34}$/', $values['account_number'])) {
            $errors['account_number'] = 'Use letters, numbers, and hyphens only.';
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
