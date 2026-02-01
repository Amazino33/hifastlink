<?php

namespace App\Filament\Resources;

use App\Models\Plan;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Resources\Resource;
use Filament\Resources\Form;
use Filament\Resources\Table as ResourceTable;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Support\Str;
use App\Filament\Resources\PlanResource\Pages;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                            ->label('Data Limit (MB)')
                            ->numeric()
                            ->formatStateUsing(fn ($state) => $state ? ($state / 1048576) : null)
                            ->dehydrateStateUsing(fn ($state) => $state ? (int) round($state * 1048576) : null),

                        TextInput::make('time_limit')
                            ->label('Time Limit (Minutes)')
                            ->numeric()
                            ->formatStateUsing(fn ($state) => $state ? ($state / 60) : null)
                            ->dehydrateStateUsing(fn ($state) => $state ? (int) round($state * 60) : null),

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

                        Toggle::make('is_family')
                            ->label('Family Plan')
                            ->helperText('Enables family admin status for subscribers'),

                        TextInput::make('family_limit')
                            ->label('Family Limit')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Maximum family members allowed'),
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
                    ->formatStateUsing(fn ($state) => $state !== null ? ((int) ($state / 1048576)) . ' MB' : null)
                    ->sortable(),

                TextColumn::make('validity_days')
                    ->label('Validity (Days)')
                    ->sortable(),

                IconColumn::make('is_family')
                    ->label('Family')
                    ->boolean()
                    ->trueIcon('heroicon-o-users')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('family_limit')
                    ->label('Family Limit')
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
