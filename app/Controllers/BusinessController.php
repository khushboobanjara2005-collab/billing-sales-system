<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BusinessContext;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Router;
use App\Core\Validator;
use App\Core\View;
use App\Models\Business;

final class BusinessController
{
    public function __construct(private Business $businesses) {}

    public function create(): void
    {
        $this->form('Create business', $this->oldValues([
            'invoice_prefix' => 'INV', 'next_invoice_number' => '1', 'default_gst_rate' => '2.50',
        ]), 'businesses/store');
    }

    public function store(): void
    {
        $result = Validator::business($_POST);
        if ($result['errors'] !== []) $this->invalid('businesses/create', $result);
        $id = $this->businesses->create(Auth::userId(), $this->databaseValues($result['values']));
        $business = $this->businesses->findOwned($id, Auth::userId());
        BusinessContext::set($id, (string) $business['business_name']);
        $this->refreshList();
        $this->messageAndRedirect('dashboard', 'Business created successfully.');
    }

    public function edit(): void
    {
        $business = $this->activeBusiness();
        $this->form('Business profile', $this->oldValues($business), 'businesses/update', (int) $business['business_id']);
    }

    public function update(): void
    {
        $id = BusinessContext::id();
        $business = $this->businesses->findOwned($id, Auth::userId());
        if ($business === null) $this->notFound();
        $result = Validator::business($_POST);
        if ((int) $result['values']['next_invoice_number'] < (int) $business['next_invoice_number']) {
            $result['errors']['next_invoice_number'] = 'The next invoice number cannot be lower than the current sequence.';
        }
        if ($result['errors'] !== []) $this->invalid('businesses/edit', $result);
        $this->businesses->updateOwned($id, Auth::userId(), $this->databaseValues($result['values']));
        $updated = $this->businesses->findOwned($id, Auth::userId());
        BusinessContext::set($id, (string) $updated['business_name']);
        $this->refreshList();
        $this->messageAndRedirect('businesses/edit', 'Business profile updated.');
    }

    public function switch(): void
    {
        $id = filter_var($_POST['business_id'] ?? null, FILTER_VALIDATE_INT);
        $business = $id !== false && $id > 0
            ? $this->businesses->findOwned((int) $id, Auth::userId()) : null;
        if ($business === null) {
            $this->messageAndRedirect('dashboard', 'That business is not available to your account.', 'error');
        }
        BusinessContext::set((int) $business['business_id'], (string) $business['business_name']);
        $this->refreshList();
        $this->messageAndRedirect('dashboard', 'Business switched.');
    }

    private function form(string $title, array $business, string $action, ?int $id = null): void
    {
        View::render('businesses/form', [
            'title' => $title, 'business' => $business, 'action' => $action, 'id' => $id,
            'csrf' => Csrf::token(), 'errors' => Flash::pull('errors', []),
            'message' => Flash::pull('message'), 'messageType' => Flash::pull('message_type', 'error'),
        ]);
    }

    private function activeBusiness(): array
    {
        $business = $this->businesses->findOwned(BusinessContext::id(), Auth::userId());
        if ($business === null) $this->notFound();
        return $business;
    }

    private function oldValues(array $defaults): array
    {
        $old = Flash::pull('old', []);
        return is_array($old) ? array_replace($defaults, $old) : $defaults;
    }

    private function databaseValues(array $values): array
    {
        foreach ([
            'tagline', 'business_type', 'owner_name', 'address', 'city', 'state', 'pincode',
            'phone', 'email', 'gstin', 'state_code', 'bank_name', 'branch_name',
            'account_holder_name', 'account_number', 'ifsc_code', 'jurisdiction',
            'authorized_signatory', 'terms_and_conditions',
        ] as $field) {
            $values[$field] = $values[$field] === '' ? null : $values[$field];
        }
        $values['next_invoice_number'] = (int) $values['next_invoice_number'];
        return $values;
    }

    private function invalid(string $route, array $result): void
    {
        Flash::set('old', $result['values']);
        Flash::set('errors', $result['errors']);
        $this->redirect($route);
    }

    private function refreshList(): void
    {
        BusinessContext::refreshList($this->businesses->allOwned(Auth::userId()));
    }

    private function messageAndRedirect(string $route, string $message, string $type = 'success'): void
    {
        Flash::set('message', $message);
        Flash::set('message_type', $type);
        $this->redirect($route);
    }

    private function redirect(string $route): void
    {
        header('Location: ' . Router::baseUrl() . '/index.php?r=' . $route, true, 303);
        exit;
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo 'Business not found.';
        exit;
    }
}
