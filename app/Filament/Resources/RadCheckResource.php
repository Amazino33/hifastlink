<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RadCheckResource\Pages;
use App\Models\RadCheck;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use BackedEnum;
use Filament\Actions\DeleteAction as ActionsDeleteAction;
use Filament\Actions\EditAction as ActionsEditAction;
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
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class RadCheckResource extends Resource
{
    use Macroable {
        Macroable::__call as dynamicMacroCall;
    }

    protected static bool $isDiscovered = true;

    /**
     * @var class-string<Model>|null
     */
    protected static ?string $model = RadCheck::class;

    protected static null|BackedEnum|string $navigationIcon = 'heroicon-o-wifi';
    protected static ?string $navigationLabel = 'Radius Users';
    protected static null|UnitEnum|string $navigationGroup = 'RADIUS Configuration';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('username')
                    ->required()
                    ->maxLength(64),
                TextInput::make('value')
                    ->label('Password')
                    ->required(),
                Hidden::make('attribute')->default('Cleartext-Password'),
                Hidden::make('op')->default(':='),
            ]);
    }

    public static function Table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')->searchable()->sortable(),
                TextColumn::make('value')->label('Password'),
                TextColumn::make('attribute')->color('gray'),
            ])
            ->actions([
                ActionsEditAction::make(),
                ActionsDeleteAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
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
            'index' => Pages\ManageRadChecks::route('/'),
        ];
    }
}