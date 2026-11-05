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

        $baseUrl = Router::baseUrl();
        ob_start();
        extract($data, EXTR_SKIP);
        require $viewFile;
        $content = (string) ob_get_clean();
        require dirname(__DIR__, 2) . '/resources/views/layouts/app.php';
    }

    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
