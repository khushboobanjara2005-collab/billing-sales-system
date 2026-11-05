<?php
declare(strict_types=1);

namespace App\Core;

final class Flash
{
    public static function set(string $key, mixed $value): void
    {
        Session::start();
        $_SESSION['_flash'][$key] = $value;
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        Session::start();
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}
