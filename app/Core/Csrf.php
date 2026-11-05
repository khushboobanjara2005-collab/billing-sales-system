<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        Session::start();
        if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function isValid(?string $submittedToken): bool
    {
        $knownToken = $_SESSION['_csrf_token'] ?? null;
        return is_string($knownToken)
            && is_string($submittedToken)
            && $submittedToken !== ''
            && hash_equals($knownToken, $submittedToken);
    }
}
