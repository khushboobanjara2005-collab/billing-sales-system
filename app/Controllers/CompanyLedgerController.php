<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Flash;
use App\Core\BusinessContext;
use App\Core\View;
use App\Models\Company;

final class CompanyLedgerController
{
    public function __construct(private Company $companies) {}

    public function show(): void
    {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $company = $id !== false && $id > 0
            ? $this->companies->find(BusinessContext::id(), (int) $id) : null;
        if ($company === null) { http_response_code(404); echo 'Company not found.'; return; }
        $from = $this->dateFilter('date_from');
        $to = $this->dateFilter('date_to');
        $status = in_array($_GET['status'] ?? '', ['Unpaid', 'Partial', 'Paid'], true) ? $_GET['status'] : '';
        $ledger = $this->companies->ledger(BusinessContext::id(), (int) $id, $from, $to, $status);
        View::render('companies/ledger', [
            'title' => 'Ledger · ' . $company['company_name'], 'company' => $company,
            'rows' => $ledger['rows'], 'totals' => $ledger['totals'],
            'filters' => ['date_from' => $from, 'date_to' => $to, 'status' => $status],
            'message' => Flash::pull('message'), 'messageType' => Flash::pull('message_type', 'success'),
        ]);
    }

    private function dateFilter(string $key): string
    {
        $value = is_string($_GET[$key] ?? null) ? $_GET[$key] : '';
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : '';
    }
}
