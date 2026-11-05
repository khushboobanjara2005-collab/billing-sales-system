<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Core\BusinessContext;
use App\Models\Reporting;
use DateTimeImmutable;

final class DashboardController
{
    public function __construct(private Reporting $reports) {}

    public function index(): void
    {
        $firstMonth = (new DateTimeImmutable('first day of this month'))
            ->modify('-5 months')->format('Y-m-01');
        $data = $this->reports->dashboard(BusinessContext::id(), $firstMonth);
        $monthlyByKey = [];
        foreach ($data['monthly'] as $row) {
            $monthlyByKey[$row['month_key']] = $row['sales'];
        }
        $months = [];
        for ($offset = 0; $offset < 6; $offset++) {
            $date = (new DateTimeImmutable($firstMonth))->modify('+' . $offset . ' months');
            $key = $date->format('Y-m');
            $months[] = ['label' => $date->format('M Y'), 'sales' => $monthlyByKey[$key] ?? '0.00'];
        }
        View::render('dashboard/index', [
            'title' => 'Dashboard', 'totals' => $data['totals'],
            'recent' => $data['recent'], 'months' => $months,
        ]);
    }
}
