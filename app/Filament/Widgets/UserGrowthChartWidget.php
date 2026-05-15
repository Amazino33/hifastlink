<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class UserGrowthChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'New Signups — Last 30 Days';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 1];

    protected function getData(): array
    {
        $tz     = 'Africa/Lagos';
        $labels = [];
        $data   = [];

        for ($i = 29; $i >= 0; $i--) {
            $date     = now($tz)->subDays($i);
            $labels[] = $date->format('M d');
            $data[]   = User::whereDate('created_at', $date->toDateString())->count();
        }

        return [
            'datasets' => [
                [
                    'label'           => 'New Users',
                    'data'            => $data,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.7)',
                    'borderColor'     => 'rgb(34, 197, 94)',
                    'borderRadius'    => 4,
                    'borderWidth'     => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales'  => [
                'y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]],
                'x' => ['ticks' => ['maxTicksLimit' => 10]],
            ],
        ];
    }
}
