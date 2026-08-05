<?php

namespace App\Filament\Resources\RouterPayoutResource\Pages;

use App\Filament\Resources\RouterPayoutResource;
use App\Models\RouterPayout;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListRouterPayouts extends ListRecords
{
    protected static string $resource = RouterPayoutResource::class;

    protected function getHeaderActions(): array
    {
        $monthOptions = [];
        for ($i = 0; $i <= 11; $i++) {
            $date  = now()->subMonths($i)->startOfMonth();
            $monthOptions[$date->format('Y-m')] = $date->format('F Y');
        }

        return [
            Action::make('generate')
                ->label('Generate Monthly Payouts')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->form([
                    Select::make('month')
                        ->label('Month')
                        ->options($monthOptions)
                        ->default(now()->subMonth()->format('Y-m'))
                        ->required()
                        ->helperText('Pending payout records will be created for all routers with owners that had revenue in this period. Routers that already have a record for this period are skipped.'),
                ])
                ->action(function (array $data) {
                    [$year, $month] = explode('-', $data['month']);
                    $count = RouterPayout::generateForMonth((int) $year, (int) $month);

                    if ($count === 0) {
                        Notification::make()
                            ->title('No new payouts generated')
                            ->body('All routers either had ₦0 revenue, or already have a record for this period.')
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title("{$count} payout(s) generated")
                            ->body('Review and approve or deny each one below.')
                            ->success()
                            ->send();
                    }
                }),

            Actions\CreateAction::make()->label('Add Manually'),
        ];
    }
}
