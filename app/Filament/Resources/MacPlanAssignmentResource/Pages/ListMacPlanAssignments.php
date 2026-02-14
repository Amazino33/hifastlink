<?php

namespace App\Filament\Resources\MacPlanAssignmentResource\Pages;

use App\Filament\Resources\MacPlanAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMacPlanAssignments extends ListRecords
{
    protected static string $resource = MacPlanAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}