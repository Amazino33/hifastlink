<?php

namespace App\Filament\Resources\VoucherBatchResource\Pages;

use App\Filament\Resources\VoucherBatchResource;
use Filament\Resources\Pages\ListRecords;

class ListVoucherBatches extends ListRecords
{
    protected static string $resource = VoucherBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
