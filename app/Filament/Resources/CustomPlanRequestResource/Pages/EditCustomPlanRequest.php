<?php

namespace App\Filament\Resources\CustomPlanRequestResource\Pages;

use App\Filament\Resources\CustomPlanRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomPlanRequest extends EditRecord
{
    protected static string $resource = CustomPlanRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}