<?php

namespace App\Filament\Resources\PartnerIntegrationResource\Pages;

use App\Filament\Resources\PartnerIntegrationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPartnerIntegrations extends ListRecords
{
    protected static string $resource = PartnerIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Integration'),
        ];
    }
}
