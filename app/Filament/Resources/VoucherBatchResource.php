<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoucherBatchResource\Pages;
use App\Models\VoucherBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class VoucherBatchResource extends Resource
{
    protected static ?string $model = VoucherBatch::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';
    protected static string|UnitEnum|null $navigationGroup = 'Plans & Billing';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Voucher Batches';

    public static function canCreate(): bool
    {
        // Batch generation is handled via the action on ListVouchers or ListVoucherBatches
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('batch_code')
                    ->label('Batch Code')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('user.name')
                    ->label('Creator')
                    ->searchable()
                    ->placeholder('System'),

                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('router.name')
                    ->label('Router')
                    ->placeholder('Global (All)')
                    ->toggleable(),

                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->alignCenter(),

                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('NGN'),

                TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->money('NGN')
                    ->weight('bold'),

                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'wallet'         => 'success',
                        'paystack'       => 'info',
                        'admin_override' => 'warning',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'wallet'         => 'Prepaid Wallet',
                        'paystack'       => 'Paystack Direct',
                        'admin_override' => 'Admin Override',
                        default          => ucfirst($state),
                    }),

                TextColumn::make('redeemed_progress')
                    ->label('Redeemed')
                    ->badge()
                    ->color(fn ($record) => $record->redeemed_count >= $record->quantity ? 'gray' : 'primary')
                    ->getStateUsing(fn ($record) => "{$record->redeemed_count} / {$record->quantity}"),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVoucherBatches::route('/'),
        ];
    }
}
