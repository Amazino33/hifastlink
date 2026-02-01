<?php

namespace App\Filament\Resources\Vouchers\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use App\Models\Plan;
use Illuminate\Support\Str;

class VoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('code')
                    ->label('Voucher Code')
                    ->default(fn () => strtoupper(Str::random(4)) . '-' . rand(1000, 9999))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('XXXX-0000'),

                Select::make('plan_id')
                    ->relationship('plan', 'name')
                    ->required()
                    ->searchable()
                    ->label('Plan to Activate')
                    ->placeholder('Select a plan'),

                Toggle::make('is_used')
                    ->label('Redeemed')
                    ->disabled()
                    ->visibleOn('edit'),

                Placeholder::make('used_by_name')
                    ->label('Redeemed By')
                    ->content(fn ($record) => $record?->is_used ? $record->user?->name : '-')
                    ->visibleOn('edit'),

                Placeholder::make('used_at')
                    ->label('Redeemed At')
                    ->content(fn ($record) => $record?->is_used ? $record->used_at?->format('M j, Y g:i A') : '-')
                    ->visibleOn('edit'),
            ]);
    }
}
