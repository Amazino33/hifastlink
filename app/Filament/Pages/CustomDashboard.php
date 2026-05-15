<?php

namespace App\Filament\Pages;

use App\Models\Router;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

class CustomDashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static null|string|BackedEnum $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort = -2;
    protected string $view = 'filament.pages.custom-dashboard';

    public function filtersForm(Schema $schema): Schema
    {
        $routers = Router::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn($r) => [
                $r->id => ($r->name ?? $r->nas_identifier) . ($r->is_online ? '  🟢' : '  🔴'),
            ]);

        return $schema->schema([
            Select::make('router_id')
                ->label('Filter by Location')
                ->options(['all' => '🌐  All Locations'] + $routers->toArray())
                ->default('all')
                ->selectablePlaceholder(false)
                ->native(false),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\OverviewStatsWidget::class,
            \App\Filament\Widgets\RevenueChartWidget::class,
            \App\Filament\Widgets\UserGrowthChartWidget::class,
            \App\Filament\Widgets\DataUsageChartWidget::class,
            \App\Filament\Widgets\RouterBreakdownWidget::class,
            \App\Filament\Widgets\RecentSessionsWidget::class,
        ];
    }

    public function getWidgetData(): array
    {
        return ['pageFilters' => $this->filters];
    }

    public function getColumns(): int|array
    {
        return [
            'sm'  => 1,
            'md'  => 2,
            'xl'  => 3,
        ];
    }
}
