<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class CustomDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.custom-dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\RouterFilterWidget::class,
            \App\Filament\Widgets\AdminStatsWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }
}