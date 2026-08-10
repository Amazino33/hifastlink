<?php

namespace App\Filament\Resources\PartnerIntegrationResource\Pages;

use App\Filament\Resources\PartnerIntegrationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPartnerIntegration extends EditRecord
{
    protected static string $resource = PartnerIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
