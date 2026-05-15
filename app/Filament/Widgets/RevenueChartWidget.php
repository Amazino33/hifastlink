<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasRouterFilter;
use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class RevenueChartWidget extends ChartWidget
{
    use InteractsWithPageFilters, HasRouterFilter;

    protected ?string $heading = 'Revenue — Last 30 Days (₦)';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];

    protected function getData(): array
    {
        $router = $this->getSelectedRouter();
        $tz     = 'Africa/Lagos';
        $labels = [];
        $data   = [];

        for ($i = 29; $i >= 0; $i--) {
            $date     = now($tz)->subDays($i);
            $labels[] = $date->format('M d');
            $data[]   = (float) Transaction::whereIn('status', ['completed', 'success'])->where('gateway', 'paystack')
                ->whereDate('created_at', $date->toDateString())
                ->when($router, fn($q) => $q->where('router_id', $router->id))
                ->sum('amount');
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Revenue (₦)',
                    'data'            => $data,
                    'fill'            => true,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.08)',
                    'borderColor'     => 'rgb(59, 130, 246)',
                    'pointRadius'     => 2,
                    'tension'         => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'callbacks' => [],
                ],
            ],
            'scales' => [
                'y' => ['beginAtZero' => true],
                'x' => ['ticks' => ['maxTicksLimit' => 10]],
            ],
        ];
    }
}
