<?php

namespace App\Filament\Resources\CustomPlanRequestResource\Pages;

use App\Filament\Resources\CustomPlanRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomPlanRequests extends ListRecords
{
    protected static string $resource = CustomPlanRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}