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
use UnitEnum;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog';

    protected static string|UnitEnum|null $navigationGroup = 'Plans & Billing';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Fieldset::make('Plan Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Plan Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Premium Monthly')
                            ->columnSpan(1),
                        TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->prefix('₦')
                            ->required()
                            ->placeholder('0.00')
                            ->columnSpan(1),
                        TextInput::make('validity_days')
                            ->label('Validity Period')
                            ->numeric()
                            ->suffix('Days')
                            ->required()
                            ->default(30)
                            ->helperText('How long this plan is valid')
                            ->columnSpan(1),
                    ])->columns(3),

                Fieldset::make('Data & Time Limits')
                    ->schema([
                        Grid::make()->schema([
                            TextInput::make('data_limit')
                                ->label('Data Allowance')
                                ->numeric()
                                ->required()
                                ->placeholder('0')
                                ->columnSpan(1),
                            Select::make('limit_unit')
                                ->label('Unit')
                                ->options([
                                    'MB' => 'Megabytes (MB)',
                                    'GB' => 'Gigabytes (GB)',
                                    'Unlimited' => 'Unlimited',
                                ])
                                ->required()
                                ->default('GB')
                                ->columnSpan(1),
                        ])->columns(2),
                        TextInput::make('time_limit')
                            ->label('Session Time Limit')
                            ->numeric()
                            ->suffix('Minutes')
                            ->helperText('Maximum session duration per connection')
                            ->formatStateUsing(fn ($state) => $state ? ($state / 60) : null)
                            ->dehydrateStateUsing(fn ($state) => $state ? (int) round($state * 60) : null)
                            ->columnSpan(2),
                    ])->columns(2),

                Fieldset::make('Speed Controls')
                    ->schema([
                        TextInput::make('speed_limit_upload')
                            ->label('Upload Speed')
                            ->numeric()
                            ->suffix('Kbps')
                            ->placeholder('Unlimited')
                            ->helperText('Leave blank for unlimited')
                            ->columnSpan(1),
                        TextInput::make('speed_limit_download')
                            ->label('Download Speed')
                            ->numeric()
                            ->suffix('Kbps')
                            ->placeholder('Unlimited')
                            ->helperText('Leave blank for unlimited')
                            ->columnSpan(1),
                    ])->columns(2),

                Fieldset::make('Family & Access Control')
                    ->schema([
                        Toggle::make('is_family')
                            ->label('Enable Family Sharing')
                            ->helperText('Allows users to share this plan with family members')
                            ->inline(false)
                            ->columnSpan(2),
                        TextInput::make('family_limit')
                            ->label('Family Members Limit')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Maximum family members (0 = disabled)')
                            ->columnSpan(1),
                        TextInput::make('max_devices')
                            ->label('Max Simultaneous Devices')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(10)
                            ->helperText('Maximum devices that can connect at once')
                            ->required()
                            ->columnSpan(1),
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
                            ->placeholder('24/7 Access')
                            ->helperText('Limit when users can connect')
                            ->nullable()
                            ->columnSpan(1),
                    ])->columns(2),
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

                TextColumn::make('max_devices')
                    ->label('Max Devices')
                    ->badge()
                    ->color('info')
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
