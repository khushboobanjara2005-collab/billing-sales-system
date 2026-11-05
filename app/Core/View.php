<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $name, array $data = []): void
    {
        $viewFile = dirname(__DIR__, 2) . '/resources/views/' . $name . '.php';
        if (!is_file($viewFile)) {
            throw new RuntimeException('Requested view was not found.');
        }

        $layout = $data['layout'] ?? 'app';
        if (!in_array($layout, ['app', 'guest'], true)) {
            throw new RuntimeException('Requested layout was not found.');
        }
        unset($data['layout']);
        $baseUrl = Router::baseUrl();
        $activeBusinessName = BusinessContext::name();
        $ownedBusinesses = BusinessContext::list();
        $userName = (string) ($_SESSION['auth_user_name'] ?? '');
        $csrf ??= Csrf::token();
        ob_start();
        extract($data, EXTR_SKIP);
        require $viewFile;
        $content = (string) ob_get_clean();
        require dirname(__DIR__, 2) . '/resources/views/layouts/' . $layout . '.php';
    }

    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
