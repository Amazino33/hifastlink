<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoucherResource\Pages;
use App\Models\Voucher;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use App\Filament\Resources\Vouchers\Schemas\VoucherForm;
use App\Filament\Resources\Vouchers\Tables\VouchersTable;
use BackedEnum;
use Filament\Schemas\Schema;
use UnitEnum;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Traits\Macroable;

class VoucherResource extends Resource
{
    use Macroable {
        Macroable::__call as dynamicMacroCall;
    }

    protected static bool $isDiscovered = true;

    /**
     * @var class-string<Model>|null
     */
    protected static ?string $model = Voucher::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';
    protected static string|UnitEnum|null $navigationGroup = 'Plans & Billing';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return VoucherForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function Table(Table $table): Table
    {
        return VouchersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            //
        ];
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