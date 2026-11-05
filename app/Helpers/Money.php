<?php
declare(strict_types=1);

namespace App\Helpers;

use InvalidArgumentException;

final class Money
{
    public static function format(string|int $amount): string
    {
        $negative = str_starts_with((string) $amount, '-');
        $value = $negative ? substr((string) $amount, 1) : (string) $amount;
        [$rupees, $paise] = array_pad(explode('.', $value, 2), 2, '0');
        $minorUnits = ((int) $rupees * 100) + (int) str_pad(substr($paise, 0, 2), 2, '0');
        $formatted = number_format(intdiv($minorUnits, 100), 0, '.', ',') . '.'
            . str_pad((string) ($minorUnits % 100), 2, '0', STR_PAD_LEFT);
        return '₹' . ($negative ? '-' : '') . $formatted;
    }

    public static function amountInWords(string $amount): string
    {
        if (!preg_match('/^[0-9]{1,10}(?:\.[0-9]{1,2})?$/', $amount)) {
            throw new InvalidArgumentException('Amount must be a non-negative rupee value.');
        }
        [$rupees, $paise] = array_pad(explode('.', $amount, 2), 2, '00');
        $rupees = (int) $rupees;
        $paise = (int) str_pad($paise, 2, '0');
        $words = self::indianNumber($rupees) . ' Rupees';
        if ($paise > 0) {
            $words .= ' and ' . self::underThousand($paise) . ' Paise';
        }
        return $words . ' Only.';
    }

    public static function sumRates(string $first, string $second): string
    {
        $toHundredths = static function (string $rate): int {
            if (!preg_match('/^[0-9]{1,3}(?:\.[0-9]{1,2})?$/', $rate)) {
                throw new InvalidArgumentException('Tax rate must be a non-negative percentage.');
            }
            [$whole, $fraction] = array_pad(explode('.', $rate, 2), 2, '00');
            return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
        };
        $total = $toHundredths($first) + $toHundredths($second);
        return intdiv($total, 100) . '.' . str_pad((string) ($total % 100), 2, '0', STR_PAD_LEFT);
    }

    private static function indianNumber(int $number): string
    {
        if ($number === 0) return 'Zero';
        $groups = [
            10000000 => 'Crore',
            100000 => 'Lakh',
            1000 => 'Thousand',
        ];
        $parts = [];
        foreach ($groups as $size => $label) {
            $count = intdiv($number, $size);
            if ($count > 0) {
                $parts[] = self::underThousand($count) . ' ' . $label;
                $number %= $size;
            }
        }
        if ($number > 0) $parts[] = self::underThousand($number);
        return implode(' ', $parts);
    }

    private static function underThousand(int $number): string
    {
        $ones = [
            0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
            14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
            18 => 'Eighteen', 19 => 'Nineteen',
        ];
        $tens = [2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'];
        $parts = [];
        if ($number >= 100) {
            $parts[] = $ones[intdiv($number, 100)] . ' Hundred';
            $number %= 100;
        }
        if ($number >= 20) {
            $part = $tens[intdiv($number, 10)];
            if ($number % 10 > 0) $part .= ' ' . $ones[$number % 10];
            $parts[] = $part;
        } elseif ($number > 0) {
            $parts[] = $ones[$number];
        }
        return implode(' ', $parts);
    }
}
