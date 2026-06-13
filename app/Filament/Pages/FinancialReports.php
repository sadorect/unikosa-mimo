<?php

namespace App\Filament\Pages;

use App\Models\Campaign;
use App\Models\Payment;
use Filament\Forms;
use Filament\Pages\Page;

class FinancialReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 25;
    protected static ?string $title = 'Financial Reports';
    protected static string $view = 'filament.pages.financial-reports';

    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public array $summary = [];
    public bool $loaded = false;

    public function mount(): void
    {
        $this->loadSummary();
    }

    public function loadSummary(): void
    {
        $query = Payment::where('status', 'successful');

        if ($this->dateFrom) {
            $query->where('paid_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('paid_at', '<=', $this->dateTo . ' 23:59:59');
        }

        $this->summary = [
            'total_raised' => (clone $query)->sum('amount'),
            'total_transactions' => (clone $query)->count(),
            'by_method' => (clone $query)->selectRaw('payment_method, count(*) as count, sum(amount) as total')
                ->groupBy('payment_method')->get()->toArray(),
            'by_currency' => (clone $query)->selectRaw('currency, count(*) as count, sum(amount) as total')
                ->groupBy('currency')->get()->toArray(),
            'campaigns' => Campaign::select('id', 'title', 'target_amount', 'raised_amount', 'currency')
                ->where('is_active', true)->get()->toArray(),
        ];

        $this->loaded = true;
    }
}
