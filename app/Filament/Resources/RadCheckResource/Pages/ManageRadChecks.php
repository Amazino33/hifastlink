<?php

namespace App\Filament\Resources\RadCheckResource\Pages;

use App\Filament\Resources\RadCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageRadChecks extends ManageRecords
{
    protected static string $resource = RadCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}