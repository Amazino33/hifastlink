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
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Number;

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
                        // TextInput::make('data_limit')
                        //     ->label('Data Limit (MB)')
                        //     ->numeric()
                        //     ->formatStateUsing(fn ($state) => $state ? ($state / 1048576) : null)
                        //     ->dehydrateStateUsing(fn ($state) => $state ? (int) round($state * 1048576) : null),

                        TextInput::make('data_limit')
                            ->label('Data Limit')
                            ->numeric()
                            ->required(),

                        Select::make('limit_unit')
                            ->label('Unit')
                            ->options([
                                'MB' => 'Megabytes (MB)',
                                'GB' => 'Gigabytes (GB)',
                                'Unlimited' => 'Unlimited',
                            ])
                            ->required(),

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

                        Select::make('allowed_login_time')
                            ->label('Time Restriction')
                            ->options([
                                null => 'No Restriction (24/7)',
                                'Al2300-0600' => 'Night Plan (11:00 PM - 6:00 AM)',
                                'Al0000-0500' => 'Midnight Owl (12:00 AM - 5:00 AM)',
                                'SaSu0000-2400' => 'Weekend Only (Sat & Sun)',
                                'Wk0800-1700' => 'Work Hours (Mon-Fri, 8 AM - 5 PM)',
                                'Al0800-1800' => 'Daytime Only (8 AM - 6 PM)',
                            ])
                            ->placeholder('Select a time rule (Optional)')
                            ->helperText('Restricts when the user can connect. Leave empty for standard plans.')
                            ->nullable(),
                    ])->columns(1),
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
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->limit_unit === 'Unlimited' || $record->data_limit === null) {
                            return 'Unlimited';
                        }

                        // If $state looks like a large number -> assume bytes already, otherwise treat as unit (MB/GB)
                        if (is_numeric($state) && (int)$state > 1048576) {
                            $bytes = (int) $state;
                        } else {
                            $bytes = $record->limit_unit === 'GB'
                                ? (int) ($state * 1073741824)
                                : (int) ($state * 1048576);
                        }
                        return Number::fileSize($bytes);
                    })
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

                TextColumn::make('allowed_login_time')
                    ->label('Login Time')
                    ->formatStateUsing(function ($state) {
                        $options = [
                            null => 'No Restriction (24/7)',
                            'Al2300-0600' => 'Night Plan (11:00 PM - 6:00 AM)',
                            'Al0000-0500' => 'Midnight Owl (12:00 AM - 5:00 AM)',
                            'SaSu0000-2400' => 'Weekend Only (Sat & Sun)',
                            'Wk0800-1700' => 'Work Hours (Mon-Fri, 8 AM - 5 PM)',
                            'Al0800-1800' => 'Daytime Only (8 AM - 6 PM)',
                        ];
                        return $options[$state] ?? $state;
                    })
                    ->sortable()
                    ->limit(25),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('name')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])
            ]);
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
