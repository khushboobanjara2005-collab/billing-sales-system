<?php
declare(strict_types=1);

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Csrf;
use App\Core\Auth;
use App\Core\BusinessContext;
use App\Core\Database;
use App\Core\Router;
use App\Core\Session;
use App\Controllers\CompaniesController;
use App\Controllers\CompanyLedgerController;
use App\Controllers\DashboardController;
use App\Controllers\InvoicesController;
use App\Controllers\PaymentsController;
use App\Controllers\ReportsController;
use App\Controllers\AuthController;
use App\Controllers\BusinessController;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Reporting;
use App\Models\Business;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;

Session::start();
$requestedRoute = strtolower(trim((string) ($_GET['r'] ?? ''), "/ \t\n\r\0\x0B"));
$publicRoutes = ['', 'health', 'auth/login', 'auth/register', 'auth/forgot-password'];
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!Auth::isAuthenticated() && !in_array($requestedRoute, $publicRoutes, true)) {
    header('Location: ' . Router::baseUrl() . '/index.php?r=auth/login', true, 303);
    exit;
}

if (Auth::isAuthenticated()) {
    $ownedBusinesses = (new Business(Database::connection()))->allOwned(Auth::userId());
    BusinessContext::refreshList($ownedBusinesses);
    $activeId = BusinessContext::id();
    $active = null;
    foreach ($ownedBusinesses as $ownedBusiness) {
        if ((int) $ownedBusiness['business_id'] === $activeId) $active = $ownedBusiness;
    }
    if ($active === null && $ownedBusinesses !== []) {
        $active = $ownedBusinesses[0];
    }
    if ($active !== null) {
        BusinessContext::set((int) $active['business_id'], (string) $active['business_name']);
    } else {
        BusinessContext::clear();
    }
    if (in_array($requestedRoute, ['auth/login', 'auth/register'], true)) {
        header('Location: ' . Router::baseUrl() . '/index.php?r=dashboard', true, 303);
        exit;
    }
    if ($ownedBusinesses === []
        && !in_array($requestedRoute, ['businesses/create', 'businesses/store', 'auth/logout', 'health'], true)) {
        header('Location: ' . Router::baseUrl() . '/index.php?r=businesses/create', true, 303);
        exit;
    }
    if ($active !== null && trim((string) ($active['jurisdiction'] ?? '')) === ''
        && !in_array($requestedRoute, [
            'businesses/create', 'businesses/store', 'businesses/edit', 'businesses/update',
            'businesses/switch', 'seller-profile/edit', 'seller-profile/update', 'auth/logout', 'health',
        ], true)) {
        header('Location: ' . Router::baseUrl() . '/index.php?r=businesses/edit', true, 303);
        exit;
    }
}

if ($method === 'POST') {
    $submittedToken = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!Csrf::isValid(is_string($submittedToken) ? $submittedToken : null)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'The request could not be verified. Reload the page and try again.';
        exit;
    }
}

$router = new Router();
$authController = static fn (): AuthController => new AuthController(
    Database::connection(), new User(Database::connection()), new Business(Database::connection())
);
$router->get('auth/login', static function () use ($authController): void { $authController()->loginForm(); });
$router->post('auth/login', static function () use ($authController): void { $authController()->login(); });
$router->get('auth/register', static function () use ($authController): void { $authController()->registerForm(); });
$router->post('auth/register', static function () use ($authController): void { $authController()->register(); });
$router->post('auth/logout', static function () use ($authController): void { $authController()->logout(); });
$router->get('auth/forgot-password', static function () use ($authController): void { $authController()->forgotPassword(); });
$router->post('auth/forgot-password', static function () use ($authController): void { $authController()->requestPasswordReset(); });
$businessController = static fn (): BusinessController => new BusinessController(new Business(Database::connection()));
$router->get('businesses/create', static function () use ($businessController): void { $businessController()->create(); });
$router->post('businesses/store', static function () use ($businessController): void { $businessController()->store(); });
$router->get('businesses/edit', static function () use ($businessController): void { $businessController()->edit(); });
$router->post('businesses/update', static function () use ($businessController): void { $businessController()->update(); });
$router->post('businesses/switch', static function () use ($businessController): void { $businessController()->switch(); });
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
$router->get('companies/ledger', static function (): void {
    $ledger = new CompanyLedgerController(new Company(Database::connection()));
    $ledger->show();
});
$invoiceController = static function (): InvoicesController {
    $pdo = Database::connection();
    return new InvoicesController(
        new Invoice($pdo), new InvoiceItem($pdo), new Company($pdo), new Business($pdo), new InvoiceService($pdo)
    );
};
$router->get('seller-profile/edit', static function () use ($businessController): void {
    $businessController()->edit();
});
$router->post('seller-profile/update', static function () use ($businessController): void {
    $businessController()->update();
});
$router->get('invoices/index', static function () use ($invoiceController): void { $invoiceController()->index(); });
$router->get('invoices/create', static function () use ($invoiceController): void { $invoiceController()->create(); });
$router->post('invoices/store', static function () use ($invoiceController): void { $invoiceController()->store(); });
$router->get('invoices/show', static function () use ($invoiceController): void { $invoiceController()->show(); });
$router->get('invoices/edit', static function () use ($invoiceController): void { $invoiceController()->edit(); });
$router->post('invoices/update', static function () use ($invoiceController): void { $invoiceController()->update(); });
$router->post('invoices/delete', static function () use ($invoiceController): void { $invoiceController()->delete(); });
$router->get('dashboard', static function (): void {
    (new DashboardController(new Reporting(Database::connection())))->index();
});
$paymentController = static function (): PaymentsController {
    $pdo = Database::connection();
    return new PaymentsController(new Payment($pdo), new Company($pdo), new PaymentService($pdo));
};
$router->get('payments/index', static function () use ($paymentController): void { $paymentController()->index(); });
$router->get('payments/create', static function () use ($paymentController): void { $paymentController()->create(); });
$router->post('payments/store', static function () use ($paymentController): void { $paymentController()->store(); });
$router->get('payments/show', static function () use ($paymentController): void { $paymentController()->show(); });
$router->post('payments/delete', static function () use ($paymentController): void { $paymentController()->delete(); });
$router->get('reports', static function (): void {
    (new ReportsController(new Reporting(Database::connection())))->index();
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
    $destination = Auth::isAuthenticated() ? 'dashboard' : 'auth/login';
    header('Location: ' . Router::baseUrl() . '/index.php?r=' . $destination, true, 303);
});

$router->dispatch((string) ($_GET['r'] ?? ''), (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
