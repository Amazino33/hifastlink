<?php

namespace App\Filament\Resources\MacPlanAssignmentResource\Pages;

use App\Filament\Resources\MacPlanAssignmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMacPlanAssignment extends CreateRecord
{
    protected static string $resource = MacPlanAssignmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}