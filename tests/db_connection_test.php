<?php
declare(strict_types=1);

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Database;

try {
    $result = Database::connection()->query('SELECT 1')->fetchColumn();
    if ((int) $result !== 1) {
        fwrite(STDERR, "FAIL: Database query returned an unexpected result.\n");
        exit(1);
    }
    fwrite(STDOUT, "PASS: Database connection and SELECT 1 succeeded.\n");
} catch (\PDOException|\RuntimeException $error) {
    app_log_error($error);
    fwrite(STDERR, "FAIL: Database connection test failed. Check local configuration and storage/logs/app.log.\n");
    exit(1);
}
