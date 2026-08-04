<?php

namespace App\Filament\Resources\RouterPayoutResource\Pages;

use App\Filament\Resources\RouterPayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRouterPayout extends EditRecord
{
    protected static string $resource = RouterPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
