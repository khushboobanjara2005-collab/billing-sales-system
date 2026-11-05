<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array<string, callable(): void>> */
    private array $routes = [];

    public function get(string $route, callable $handler): void
    {
        $this->add('GET', $route, $handler);
    }

    public function post(string $route, callable $handler): void
    {
        $this->add('POST', $route, $handler);
    }

    public function dispatch(string $route, string $method): void
    {
        $route = $this->normalize($route);
        $method = strtoupper($method);
        if (!isset($this->routes[$route])) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Page not found.';
            return;
        }

        if (!isset($this->routes[$route][$method])) {
            http_response_code(405);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Method not allowed.';
            return;
        }

        ($this->routes[$route][$method])();
    }

    private function add(string $method, string $route, callable $handler): void
    {
        $this->routes[$this->normalize($route)][$method] = $handler;
    }

    public static function baseUrl(): string
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $directory = dirname($scriptName);
        return $directory === '/' || $directory === '.' ? '' : rtrim($directory, '/');
    }

    private function normalize(string $route): string
    {
        return trim(strtolower($route), "/ \t\n\r\0\x0B");
    }
}
