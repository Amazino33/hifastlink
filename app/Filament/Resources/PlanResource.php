<?php

namespace App\Filament\Resources;

use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Resources\Resource;
use Filament\Resources\Form;
use Filament\Resources\Table as ResourceTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Str;
use App\Filament\Resources\PlanResource\Pages;
use BackedEnum;
use Filament\Schemas\Schema;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->required(),

                TextInput::make('price')
                    ->label('Price')
                    ->numeric()
                    ->prefix('₦'),

                Fieldset::make('Limits')
                    ->schema([
                        TextInput::make('data_limit')
                            ->label('Data Limit')
                            ->numeric()
                            ->suffix('MB'),

                        TextInput::make('time_limit')
                            ->label('Time Limit')
                            ->numeric()
                            ->suffix('Minutes'),

                        Grid::make()->schema([
                            TextInput::make('speed_limit_upload')
                                ->label('Upload (Kbps)')
                                ->numeric(),

                            TextInput::make('speed_limit_download')
                                ->label('Download (Kbps)')
                                ->numeric(),
                        ])->columns(2),

                        TextInput::make('validity_days')
                            ->label('Validity')
                            ->numeric()
                            ->suffix('Days'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => $state !== null ? '₦' . number_format($state, 2) : null)
                    ->sortable(),

                TextColumn::make('data_limit')
                    ->label('Data Limit')
                    ->formatStateUsing(fn ($state) => $state !== null ? ((int) $state) . ' MB' : null)
                    ->sortable(),

                TextColumn::make('validity_days')
                    ->label('Validity (Days)')
                    ->sortable(),
            ])
            ->defaultSort('name');
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
