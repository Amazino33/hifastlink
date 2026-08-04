<?php

namespace App\Filament\Resources\RouterPayoutResource\Pages;

use App\Filament\Resources\RouterPayoutResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRouterPayout extends CreateRecord
{
    protected static string $resource = RouterPayoutResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
