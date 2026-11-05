<?php
declare(strict_types=1);

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Router;
use App\Core\Session;
use App\Controllers\CompaniesController;
use App\Models\Company;

Session::start();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedToken = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!Csrf::isValid(is_string($submittedToken) ? $submittedToken : null)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'The request could not be verified. Reload the page and try again.';
        exit;
    }
}

$router = new Router();
$companyController = static fn (): CompaniesController => new CompaniesController(
    new Company(Database::connection())
);
$router->get('companies/index', static function () use ($companyController): void {
    $companyController()->index();
});
$router->get('companies/create', static function () use ($companyController): void {
    $companyController()->create();
});
$router->post('companies/store', static function () use ($companyController): void {
    $companyController()->store();
});
$router->get('companies/edit', static function () use ($companyController): void {
    $companyController()->edit();
});
$router->post('companies/update', static function () use ($companyController): void {
    $companyController()->update();
});
$router->post('companies/delete', static function () use ($companyController): void {
    $companyController()->delete();
});
$router->get('companies/show', static function () use ($companyController): void {
    $companyController()->show();
});
$router->get('dashboard', static function (): void {
    \App\Core\View::render('placeholder', ['title' => 'Dashboard']);
});
$router->get('invoices', static function (): void {
    \App\Core\View::render('placeholder', ['title' => 'Invoices']);
});
$router->get('payments', static function (): void {
    \App\Core\View::render('placeholder', ['title' => 'Payments']);
});
$router->get('reports', static function (): void {
    \App\Core\View::render('placeholder', ['title' => 'Reports']);
});
$router->get('health', static function (): void {
    $databaseReachable = false;
    try {
        $databaseReachable = (int) Database::connection()->query('SELECT 1')->fetchColumn() === 1;
    } catch (\PDOException|\RuntimeException $error) {
        app_log_error($error);
    }

    http_response_code($databaseReachable ? 200 : 503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'application' => 'Billing and Sales Management System',
        'status' => $databaseReachable ? 'ok' : 'degraded',
        'database_reachable' => $databaseReachable,
        'base_url' => Router::baseUrl(),
    ], JSON_UNESCAPED_SLASHES);
});
$router->get('', static function (): void {
    header('Content-Type: text/html; charset=utf-8');
    $healthUrl = htmlspecialchars(Router::baseUrl() . '/index.php?r=health', ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Billing Sales System</title>'
        . '<h1>Billing and Sales Management System</h1><p>Foundation is ready.</p>'
        . '<p><a href="' . $healthUrl . '">Check application health</a></p></html>';
});

$router->dispatch((string) ($_GET['r'] ?? ''), (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
