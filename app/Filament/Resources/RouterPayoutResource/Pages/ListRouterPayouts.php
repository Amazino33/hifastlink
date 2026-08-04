<?php

namespace App\Filament\Resources\RouterPayoutResource\Pages;

use App\Filament\Resources\RouterPayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRouterPayouts extends ListRecords
{
    protected static string $resource = RouterPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Payout'),
        ];
    }
}
