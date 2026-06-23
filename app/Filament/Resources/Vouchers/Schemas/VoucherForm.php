<?php

namespace App\Filament\Resources\Vouchers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class VoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Identity')
                    ->description('Basic voucher details')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        TextInput::make('code')
                            ->label('Voucher Code')
                            ->default(fn () => 'VCH-' . strtoupper(Str::random(8)))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Auto-generated — edit only if needed'),

                        TextInput::make('label')
                            ->label('Label')
                            ->placeholder('e.g. Welcome gift, Event promo, Staff access')
                            ->maxLength(100),

                        Select::make('plan_id')
                            ->relationship('plan', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? 'Plan #' . $record->id)
                            ->searchable()
                            ->live()
                            ->label('Linked Plan')
                            ->placeholder('None — custom specs below')
                            ->helperText('When a plan is linked, duration/data/speed are inherited from the plan'),

                        Select::make('router_id')
                            ->relationship('router', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? 'Router #' . $record->id)
                            ->searchable()
                            ->label('Router')
                            ->placeholder('Any router')
                            ->helperText('Restrict this voucher to a specific access point'),

                        Hidden::make('created_by')
                            ->default(fn () => auth()->id()),
                    ])
                    ->columns(2),

                Section::make('Access Settings')
                    ->description('Validity, data cap, and speed limits for this voucher')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        TextInput::make('max_uses')
                            ->label('Uses per Code')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(500)
                            ->default(1)
                            ->required()
                            ->helperText('How many devices can redeem this code'),

                        TextInput::make('duration_hours')
                            ->label('Duration (hours)')
                            ->numeric()
                            ->minValue(1)
                            ->default(72)
                            ->required(fn (Get $get): bool => ! $get('plan_id'))
                            ->dehydrated(fn (Get $get): bool => ! $get('plan_id'))
                            ->helperText('24 h = 1 day · 168 h = 1 week · 720 h = 30 days')
                            ->hidden(fn (Get $get): bool => (bool) $get('plan_id')),

                        Toggle::make('is_unlimited')
                            ->label('Unlimited Data')
                            ->live()
                            ->columnSpanFull()
                            ->hidden(fn (Get $get): bool => (bool) $get('plan_id')),

                        TextInput::make('data_limit_mb')
                            ->label('Data Allowance (MB)')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('e.g. 5120 for 5 GB')
                            ->helperText('1 GB = 1024 MB')
                            ->hidden(fn (Get $get): bool => (bool) $get('is_unlimited') || (bool) $get('plan_id')),

                        TextInput::make('speed_limit_download')
                            ->label('Download Speed (Kbps)')
                            ->numeric()
                            ->minValue(0)
                            ->placeholder('e.g. 2048')
                            ->helperText('0 or empty = no limit')
                            ->hidden(fn (Get $get): bool => (bool) $get('plan_id')),

                        TextInput::make('speed_limit_upload')
                            ->label('Upload Speed (Kbps)')
                            ->numeric()
                            ->minValue(0)
                            ->placeholder('e.g. 512')
                            ->helperText('0 or empty = no limit')
                            ->hidden(fn (Get $get): bool => (bool) $get('plan_id')),
                    ])
                    ->columns(2),

                Section::make('Redemption Status')
                    ->description('Set automatically when the voucher is used — see View page for full details')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        Toggle::make('is_used')
                            ->label('Marked as Used')
                            ->disabled()
                            ->helperText('Managed automatically — toggle only for manual correction'),
                    ])
                    ->visibleOn('edit'),
            ]);
    }
}
