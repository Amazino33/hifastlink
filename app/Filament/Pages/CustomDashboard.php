<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use BackedEnum;

class CustomDashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected string $view = 'filament.pages.custom-dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\RouterFilterWidget::class,
            \App\Filament\Widgets\AdminStatsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}