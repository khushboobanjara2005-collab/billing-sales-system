<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Validator;
use App\Core\View;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoiceService;
use DomainException;
use InvalidArgumentException;
use PDOException;

final class InvoicesController
{
    private const PAGE_SIZE = 10;

    public function __construct(private Invoice $invoices, private InvoiceItem $items,
        private Company $companies, private InvoiceService $service) {}
    public function index(): void {
        $companyId = filter_var($_GET['company_id'] ?? 0, FILTER_VALIDATE_INT);
        $companyId = $companyId === false ? 0 : max(0, $companyId);
        $from = $this->filterDate(is_string($_GET['date_from'] ?? null) ? $_GET['date_from'] : '');
        $to = $this->filterDate(is_string($_GET['date_to'] ?? null) ? $_GET['date_to'] : '');
        $status = in_array($_GET['status'] ?? '', ['Unpaid', 'Partial', 'Paid'], true) ? $_GET['status'] : '';
        $number = is_string($_GET['number'] ?? null) ? trim($_GET['number']) : '';
        $page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
        $result = $this->invoices->list(
            $number, $companyId, $from, $to, $status,
            self::PAGE_SIZE, ($page - 1) * self::PAGE_SIZE
        );
        View::render('invoices/index', [
            'title' => 'Invoices', 'invoices' => $result['rows'], 'total' => $result['total'],
            'page' => $page, 'pages' => max(1, (int) ceil($result['total'] / self::PAGE_SIZE)),
            'companies' => $this->companies->allForSelect(),
            'filters' => ['number' => $number, 'company_id' => $companyId, 'date_from' => $from, 'date_to' => $to, 'status' => $status],
            'message' => Flash::pull('message'), 'messageType' => Flash::pull('message_type', 'success'),
        ]);
    }
    public function create(): void { $this->form('Create invoice', [], null); }
    public function store(): void {
        $result = Validator::invoice($_POST);
        $this->checkCompany($result);
        if ($result['errors'] !== []) $this->invalid('invoices/create', $result);
        try {
            $id = $this->service->create($result['values']);
            $this->redirectMessage('invoices/show&id=' . $id, 'Invoice created successfully.');
        } catch (DomainException|InvalidArgumentException $error) {
            $this->invalid('invoices/create', $result, $error->getMessage());
        } catch (PDOException $error) {
            app_log_error($error);
            $this->invalid('invoices/create', $result, 'Unable to save invoice. Please try again.');
        }
    }
    public function show(): void {
        $id = $this->queryId();
        $invoice = $this->invoices->findDetails($id);
        if ($invoice === null) $this->notFound();
        View::render('invoices/show', [
            'title' => 'Invoice ' . $invoice['invoice_number'], 'invoice' => $invoice,
            'items' => $this->items->forInvoice($id), 'message' => Flash::pull('message'),
            'messageType' => Flash::pull('message_type', 'success'),
        ]);
    }
    public function edit(): void {
        $id = $this->queryId();
        $invoice = $this->invoices->findDetails($id);
        if ($invoice === null) $this->notFound();
        $this->form('Edit invoice ' . $invoice['invoice_number'], $invoice, $id, $this->items->forInvoice($id));
    }
    public function update(): void {
        $id = $this->postId();
        $result = Validator::invoice($_POST);
        $this->checkCompany($result);
        if ($result['errors'] !== []) $this->invalid('invoices/edit&id=' . $id, $result);
        try {
            $this->service->update($id, $result['values']);
            $this->redirectMessage('invoices/show&id=' . $id, 'Invoice updated successfully.');
        } catch (DomainException|InvalidArgumentException $error) {
            $this->invalid('invoices/edit&id=' . $id, $result, $error->getMessage());
        } catch (PDOException $error) {
            app_log_error($error);
            $this->invalid('invoices/edit&id=' . $id, $result, 'Unable to update invoice. Please try again.');
        }
    }
    public function delete(): void {
        $id = $this->postId();
        try {
            $this->service->delete($id);
            $this->redirectMessage('invoices/index', 'Invoice deleted successfully.');
        } catch (DomainException $error) {
            $message = $error->getMessage() === 'This invoice has payments.'
                ? 'Cannot delete: this invoice has payments'
                : 'Invoice not found.';
            $this->redirectMessage('invoices/index', $message, 'error');
        } catch (PDOException $error) {
            app_log_error($error);
            $this->redirectMessage('invoices/index', 'The invoice could not be deleted.', 'error');
        }
    }
    private function form(string $title, array $invoice, ?int $id, array $items = []): void {
        $old = Flash::pull('old', []);
        $errors = Flash::pull('errors', []);
        if (is_array($old)) $invoice = array_replace($invoice, $old);
        if (isset($old['items']) && is_array($old['items'])) $items = $old['items'];
        View::render('invoices/form', [
            'title' => $title, 'invoice' => $invoice, 'items' => $items, 'errors' => $errors,
            'id' => $id, 'companies' => $this->companies->allForSelect(), 'csrf' => Csrf::token(),
            'message' => Flash::pull('message'), 'messageType' => Flash::pull('message_type', 'error'),
        ]);
    }
    private function checkCompany(array &$result): void {
        $id = (int) ($result['values']['company_id'] ?? 0);
        if ($id > 0 && $this->companies->find($id) === null) $result['errors']['company_id'] = 'Choose an existing company.';
    }
    private function invalid(string $route, array $result, string $message = ''): void {
        Flash::set('old', $result['values']);
        Flash::set('errors', $result['errors']);
        if ($message !== '') { Flash::set('message', $message); Flash::set('message_type', 'error'); }
        $this->redirect($route);
    }
    private function filterDate(string $date): string {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date ? $date : '';
    }
    private function queryId(): int {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) $this->notFound();
        return (int) $id;
    }
    private function postId(): int {
        $id = filter_var($_POST['invoice_id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) $this->notFound();
        return (int) $id;
    }
    private function redirectMessage(string $route, string $message, string $type = 'success'): void {
        Flash::set('message', $message); Flash::set('message_type', $type);
        $this->redirect($route);
    }
    private function redirect(string $route): void {
        header('Location: ' . \App\Core\Router::baseUrl() . '/index.php?r=' . $route, true, 303);
        exit;
    }
    private function notFound(): void { http_response_code(404); echo 'Invoice not found.'; exit; }
}
