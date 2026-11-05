<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function userId(): int
    {
        return (int) ($_SESSION['auth_user_id'] ?? 0);
    }

    public static function isAuthenticated(): bool
    {
        return self::userId() > 0;
    }

    public static function login(array $user): void
    {
        Session::start();
        session_regenerate_id(true);
        unset($_SESSION['_csrf_token']);
        $_SESSION['auth_user_id'] = (int) $user['user_id'];
        $_SESSION['auth_user_name'] = (string) $user['full_name'];
    }

    public static function logout(): void
    {
        Session::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }
}
