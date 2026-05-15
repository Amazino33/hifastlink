<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasRouterFilter;
use App\Models\RadAcct;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class DataUsageChartWidget extends ChartWidget
{
    use InteractsWithPageFilters, HasRouterFilter;

    protected ?string $heading = 'Data Usage — Last 14 Days (MB)';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $router = $this->getSelectedRouter();
        $tz     = 'Africa/Lagos';
        $labels = [];
        $data   = [];

        for ($i = 13; $i >= 0; $i--) {
            $date     = now($tz)->subDays($i);
            $labels[] = $date->format('M d');

            $bytes = (int) RadAcct::whereDate('acctstarttime', $date->toDateString())
                ->when($router, fn($q) => $this->applyRouterFilter($q, $router))
                ->sum(DB::raw('COALESCE(acctinputoctets,0) + COALESCE(acctoutputoctets,0)'));

            $data[] = round($bytes / 1048576, 2); // bytes → MB
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Data Used (MB)',
                    'data'            => $data,
                    'fill'            => true,
                    'backgroundColor' => 'rgba(168, 85, 247, 0.08)',
                    'borderColor'     => 'rgb(168, 85, 247)',
                    'pointRadius'     => 3,
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
            'plugins' => ['legend' => ['display' => false]],
            'scales'  => [
                'y' => ['beginAtZero' => true],
            ],
        ];
    }
}
