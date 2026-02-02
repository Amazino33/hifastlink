<?php

namespace App\Filament\Resources\RadGroupReplies\Pages;

use App\Filament\Resources\RadGroupReplies\RadGroupReplyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRadGroupReplies extends ManageRecords
{
    protected static string $resource = RadGroupReplyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
