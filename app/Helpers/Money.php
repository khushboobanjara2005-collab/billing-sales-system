<?php
declare(strict_types=1);

namespace App\Helpers;

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
}
