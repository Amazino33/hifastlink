<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RadCheckResource\Pages;
use App\Models\RadCheck;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class RadCheckResource extends Resource
{
    protected static ?string $model = RadCheck::class;

    protected static null|BackedEnum|string $navigationIcon = 'heroicon-o-wifi';
    protected static ?string $navigationLabel = 'Radius Users';
    protected static null|UnitEnum|string $navigationGroup = 'RADIUS Configuration';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('username')
                    ->required()
                    ->maxLength(64),
                Forms\Components\TextInput::make('value')
                    ->label('Password')
                    ->required(),
                Forms\Components\Hidden::make('attribute')->default('Cleartext-Password'),
                Forms\Components\Hidden::make('op')->default(':='),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('value')->label('Password'),
                Tables\Columns\TextColumn::make('attribute')->color('gray'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRadChecks::route('/'),
        ];
    }
}