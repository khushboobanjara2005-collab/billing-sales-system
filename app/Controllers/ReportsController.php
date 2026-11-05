<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Reporting;
use DateTimeImmutable;

final class ReportsController
{
    public function __construct(private Reporting $reports) {}

    public function index(): void
    {
        $from = $this->dateFilter('date_from');
        $to = $this->dateFilter('date_to');
        $error = $from !== '' && $to !== '' && $from > $to
            ? 'The start date must be on or before the end date.' : '';
        if ($error !== '') { $from = ''; $to = ''; }
        View::render('reports/index', [
            'title' => 'Reports', 'dateFrom' => $from, 'dateTo' => $to, 'dateError' => $error,
            'sales' => $this->reports->salesSummary($from, $to),
            'companies' => $this->reports->companyOutstanding(),
            'methods' => $this->reports->paymentMethods($from, $to),
        ]);
    }

    private function dateFilter(string $key): string
    {
        $value = is_string($_GET[$key] ?? null) ? $_GET[$key] : '';
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : '';
    }
}
