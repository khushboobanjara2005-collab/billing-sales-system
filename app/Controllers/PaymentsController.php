<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Router;
use App\Core\View;
use App\Models\Company;
use App\Models\Payment;
use App\Services\PaymentService;
use DomainException;
use InvalidArgumentException;
use PDOException;

final class PaymentsController
{
    private const PAGE_SIZE = 10;

    public function __construct(private Payment $payments, private Company $companies, private PaymentService $service) {}
    public function index(): void {
        $companyId = filter_var($_GET['company_id'] ?? 0, FILTER_VALIDATE_INT);
        $companyId = $companyId === false ? 0 : max(0, $companyId);
        $method = in_array($_GET['method'] ?? '', ['Cash', 'UPI', 'Bank Transfer', 'Cheque'], true) ? $_GET['method'] : '';
        $from = $this->dateFilter('date_from');
        $to = $this->dateFilter('date_to');
        $page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
        $result = $this->payments->paginate($companyId, $method, $from, $to, self::PAGE_SIZE, ($page - 1) * self::PAGE_SIZE);
        View::render('payments/index', [
            'title' => 'Payments', 'payments' => $result['rows'], 'total' => $result['total'],
            'page' => $page, 'pages' => max(1, (int) ceil($result['total'] / self::PAGE_SIZE)),
            'companies' => $this->companies->allForSelect(),
            'filters' => ['company_id' => $companyId, 'method' => $method, 'date_from' => $from, 'date_to' => $to],
            'message' => Flash::pull('message'), 'messageType' => Flash::pull('message_type', 'success'),
        ]);
    }

    public function create(): void {
        $old = Flash::pull('old', []);
        $this->form(is_array($old) ? $old : [], Flash::pull('errors', []), Flash::pull('message'));
    }

    public function store(): void {
        $values = [
            'invoice_id' => $this->input('invoice_id'), 'payment_date' => $this->input('payment_date'),
            'amount' => $this->input('amount'), 'payment_method' => $this->input('payment_method'),
        ];
        $errors = $this->validate($values);
        if ($errors !== []) $this->invalid($values, $errors);
        try {
            $id = $this->service->record((int) $values['invoice_id'], $values['payment_date'], $values['amount'], $values['payment_method']);
            $this->redirectMessage('payments/show&id=' . $id, 'Payment recorded successfully.');
        } catch (DomainException|InvalidArgumentException $error) {
            $this->invalid($values, [], $error->getMessage());
        } catch (PDOException $error) {
            app_log_error($error);
            $this->invalid($values, [], 'Unable to record payment. Please try again.');
        }
    }

    public function show(): void {
        $id = $this->queryId();
        $payment = $this->payments->findDetails($id);
        if ($payment === null) $this->notFound();
        View::render('payments/show', [
            'title' => 'Payment details', 'payment' => $payment,
            'message' => Flash::pull('message'), 'messageType' => Flash::pull('message_type', 'success'),
        ]);
    }

    public function delete(): void {
        $id = filter_var($_POST['payment_id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) $this->notFound();
        try {
            $this->service->delete((int) $id);
            $this->redirectMessage('payments/index', 'Payment deleted successfully.');
        } catch (DomainException $error) {
            $this->redirectMessage('payments/index', 'Payment not found.', 'error');
        } catch (PDOException $error) {
            app_log_error($error);
            $this->redirectMessage('payments/index', 'The payment could not be deleted.', 'error');
        }
    }

    private function form(array $values, array $errors, ?string $message = null): void {
        $invoiceId = (int) ($values['invoice_id'] ?? $_GET['invoice_id'] ?? 0);
        $this->renderForm($values, $errors, $message, $invoiceId);
    }

    private function renderForm(array $values, array $errors, ?string $message, int $invoiceId): void {
        View::render('payments/form', [
            'title' => 'Record payment', 'values' => $values, 'errors' => $errors,
            'invoices' => $this->payments->invoicesForSelect(), 'invoiceId' => $invoiceId,
            'csrf' => Csrf::token(), 'message' => $message,
        ]);
    }

    private function validate(array $values): array {
        $errors = [];
        if ($values['invoice_id'] === '' || filter_var($values['invoice_id'], FILTER_VALIDATE_INT) === false
            || (int) $values['invoice_id'] < 1) $errors['invoice_id'] = 'Choose an invoice.';
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $values['payment_date']);
        if (!$date || $date->format('Y-m-d') !== $values['payment_date'] || $values['payment_date'] > date('Y-m-d')) {
            $errors['payment_date'] = 'Enter a valid payment date that is not in the future.';
        }
        if (!preg_match('/^(?:0|[1-9][0-9]{0,9})(?:\.[0-9]{1,2})?$/', $values['amount'])
            || !preg_match('/[1-9]/', $values['amount'])) $errors['amount'] = 'Enter an amount greater than zero with up to 2 decimals.';
        if (!in_array($values['payment_method'], ['Cash', 'UPI', 'Bank Transfer', 'Cheque'], true)) {
            $errors['payment_method'] = 'Choose a valid payment method.';
        }
        return $errors;
    }

    private function invalid(array $values, array $errors, string $message = ''): void {
        Flash::set('old', $values); Flash::set('errors', $errors);
        if ($message !== '') Flash::set('message', $message);
        $this->redirect('payments/create');
    }

    private function dateFilter(string $key): string {
        $value = is_string($_GET[$key] ?? null) ? $_GET[$key] : '';
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : '';
    }

    private function input(string $key): string {
        return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    }

    private function queryId(): int {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) $this->notFound();
        return (int) $id;
    }

    private function redirectMessage(string $route, string $message, string $type = 'success'): void {
        Flash::set('message', $message); Flash::set('message_type', $type);
        $this->redirect($route);
    }

    private function redirect(string $route): void {
        header('Location: ' . Router::baseUrl() . '/index.php?r=' . $route, true, 303); exit;
    }

    private function notFound(): void { http_response_code(404); echo 'Payment not found.'; exit; }
}
