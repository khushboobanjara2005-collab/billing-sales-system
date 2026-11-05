<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Validator;
use App\Core\View;
use App\Models\Company;
use PDOException;

final class CompaniesController
{
    private const PAGE_SIZE = 10;

    public function __construct(private Company $companies) {}

    public function index(): void {
        $search = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
        $result = $this->companies->paginate($search, self::PAGE_SIZE, ($page - 1) * self::PAGE_SIZE);
        View::render('companies/index', [
            'title' => 'Companies', 'companies' => $result['rows'], 'total' => $result['total'],
            'page' => $page, 'pages' => max(1, (int) ceil($result['total'] / self::PAGE_SIZE)),
            'search' => $search, 'message' => Flash::pull('message'),
            'messageType' => Flash::pull('message_type', 'success'),
        ]);
    }

    public function create(): void { $this->form('Create company', [], []); }

    public function store(): void {
        $result = Validator::company($_POST, fn (string $gstin): bool => $this->companies->gstinExists($gstin));
        if ($result['errors'] !== []) $this->invalidForm('companies/create', $result);
        try {
            $this->companies->create($this->databaseValues($result['values']));
            $this->redirectWithMessage('companies/index', 'Company created successfully.');
        } catch (PDOException $error) {
            $this->saveError($error, 'companies/create', $result['values']);
        }
    }

    public function edit(): void {
        $company = $this->companyFromQuery();
        $this->form('Edit company', $company, [], (int) $company['company_id']);
    }

    public function update(): void {
        $id = $this->postId();
        if ($this->companies->find($id) === null) $this->notFound();
        $result = Validator::company($_POST, fn (string $gstin): bool => $this->companies->gstinExists($gstin, $id));
        if ($result['errors'] !== []) $this->invalidForm('companies/edit&id=' . $id, $result);
        try {
            $this->companies->update($id, $this->databaseValues($result['values']));
            $this->redirectWithMessage('companies/index', 'Company updated successfully.');
        } catch (PDOException $error) {
            $this->saveError($error, 'companies/edit&id=' . $id, $result['values']);
        }
    }

    public function delete(): void {
        $id = $this->postId();
        try {
            $this->companies->delete($id);
            $this->redirectWithMessage('companies/index', 'Company deleted successfully.');
        } catch (PDOException $error) {
            app_log_error($error);
            $message = (string) $error->getCode() === '23000'
                ? 'Cannot delete: this company has invoices'
                : 'The company could not be deleted. Please try again.';
            $this->redirectWithMessage('companies/index', $message, 'error');
        }
    }

    public function show(): void {
        $company = $this->companyFromQuery();
        View::render('companies/show', [
            'title' => 'Company details', 'company' => $company,
            'message' => Flash::pull('message'), 'messageType' => Flash::pull('message_type', 'success'),
        ]);
    }

    private function form(string $title, array $company, array $errors, ?int $id = null): void {
        $old = Flash::pull('old', []);
        $storedErrors = Flash::pull('errors', []);
        if (is_array($old)) $company = array_replace($company, $old);
        if (is_array($storedErrors)) $errors = $storedErrors;
        View::render('companies/form', [
            'title' => $title, 'company' => $company, 'errors' => $errors,
            'action' => $id === null ? 'companies/store' : 'companies/update',
            'id' => $id, 'csrf' => Csrf::token(), 'message' => Flash::pull('message'),
            'messageType' => Flash::pull('message_type', 'error'),
        ]);
    }

    private function saveError(PDOException $error, string $route, array $values): void {
        app_log_error($error);
        $duplicate = (string) $error->getCode() === '23000';
        Flash::set('message', $duplicate ? 'This GSTIN is already registered.' : 'Unable to save company. Please try again.');
        Flash::set('message_type', 'error');
        $this->invalidForm($route, [
            'values' => $values, 'errors' => $duplicate ? ['gstin' => 'This GSTIN is already registered.'] : [],
        ]);
    }

    private function invalidForm(string $route, array $result): void {
        Flash::set('old', $result['values']);
        Flash::set('errors', $result['errors']);
        $this->redirect($route);
    }

    private function databaseValues(array $values): array {
        foreach (['gstin', 'phone', 'email', 'address'] as $field) {
            $values[$field] = $values[$field] === '' ? null : $values[$field];
        }
        return $values;
    }

    private function companyFromQuery(): array {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $company = $id !== false && $id > 0 ? $this->companies->find((int) $id) : null;
        if ($company === null) $this->notFound();
        return $company;
    }

    private function postId(): int {
        $id = filter_var($_POST['company_id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) $this->notFound();
        return (int) $id;
    }

    private function redirectWithMessage(string $route, string $message, string $type = 'success'): void {
        Flash::set('message', $message);
        Flash::set('message_type', $type);
        $this->redirect($route);
    }

    private function redirect(string $route): void {
        header('Location: ' . \App\Core\Router::baseUrl() . '/index.php?r=' . $route, true, 303);
        exit;
    }

    private function notFound(): void {
        http_response_code(404);
        echo 'Company not found.';
        exit;
    }
}
