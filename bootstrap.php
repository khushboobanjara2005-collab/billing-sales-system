<?php
declare(strict_types=1);

$projectRoot = __DIR__;
$envFile = $projectRoot . DIRECTORY_SEPARATOR . '.env';

if (is_file($envFile) && is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim(trim($value), "\"'");
        if ($name !== '' && getenv($name) === false) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR
        . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Kolkata');

function app_log_error(Throwable $error): void
{
    $logDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($logDirectory) && !mkdir($logDirectory, 0750, true) && !is_dir($logDirectory)) {
        error_log('Application log directory could not be created.');
        return;
    }

    $entry = sprintf(
        "[%s] %s: %s in %s:%d\n%s\n",
        date(DATE_ATOM),
        get_class($error),
        $error->getMessage(),
        $error->getFile(),
        $error->getLine(),
        $error->getTraceAsString()
    );
    if (file_put_contents($logDirectory . DIRECTORY_SEPARATOR . 'app.log', $entry, FILE_APPEND | LOCK_EX) === false) {
        error_log('Application error could not be written to the log file.');
    }
}

set_exception_handler(static function (Throwable $error): void {
    app_log_error($error);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo 'An unexpected error occurred. Please check the application log.';
});
