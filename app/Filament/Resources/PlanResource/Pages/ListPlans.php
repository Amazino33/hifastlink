<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ListPlans extends ListRecords
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
