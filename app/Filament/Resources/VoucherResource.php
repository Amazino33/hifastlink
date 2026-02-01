<?php

namespace App\Filament\Resources;

use App\Models\Voucher;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use App\Filament\Resources\Vouchers\Schemas\VoucherForm;
use App\Filament\Resources\Vouchers\Tables\VouchersTable;
use App\Filament\Resources\VoucherResource\Pages;
use BackedEnum;
use Filament\Schemas\Schema;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    public static function table(Table $table): Table
    {
        return VouchersTable::configure($table);
    }

    public static function form(Schema $schema): Schema
    {
        return VoucherForm::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVouchers::route('/'),
            'create' => Pages\CreateVoucher::route('/create'),
            'edit' => Pages\EditVoucher::route('/{record}/edit'),
        ];
    }
}