<?php

namespace App\Filament\Resources\MacPlanAssignmentResource\Pages;

use App\Filament\Resources\MacPlanAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMacPlanAssignment extends EditRecord
{
    protected static string $resource = MacPlanAssignmentResource::class;

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