<?php

namespace App\Filament\Resources;

use App\Models\Transaction;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\TransactionResource\Pages;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use BackedEnum;
use UnitEnum;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Plans & Billing';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Transactions';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Customer')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required()
                    ->columnSpan(1),

                Select::make('plan_id')
                    ->label('Subscription Plan')
                    ->relationship('plan', 'name')
                    ->required()
                    ->columnSpan(1),

                TextInput::make('amount')
                    ->label('Amount Paid')
                    ->numeric()
                    ->prefix('₦')
                    ->required()
                    ->columnSpan(1),

                TextInput::make('reference')
                    ->label('Payment Reference')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('TXN-XXXXXXX')
                    ->unique(ignoreRecord: true)
                    ->columnSpan(1),

                Select::make('gateway')
                    ->label('Payment Gateway')
                    ->options([
                        'paystack' => 'Paystack',
                        'flutterwave' => 'Flutterwave',
                        'manual' => 'Manual Payment',
                        'voucher' => 'Voucher Code',
                    ])
                    ->required()
                    ->default('manual')
                    ->columnSpan(1),

                Select::make('status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->default('pending')
                    ->columnSpan(1),

                DateTimePicker::make('paid_at')
                    ->label('Payment Date')
                    ->default(now())
                    ->columnSpan(2),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Reference copied!')
                    ->copyMessageDuration(1500),

                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->badge()
                    ->color('info'),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('NGN')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'secondary' => 'cancelled',
                    ])
                    ->sortable(),

                TextColumn::make('gateway')
                    ->label('Gateway')
                    ->badge()
                    ->colors([
                        'primary' => 'paystack',
                        'info' => 'flutterwave',
                        'secondary' => 'manual',
                        'success' => 'voucher',
                    ]),

                TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('gateway')
                    ->options([
                        'paystack' => 'Paystack',
                        'flutterwave' => 'Flutterwave',
                        'manual' => 'Manual',
                        'voucher' => 'Voucher',
                    ]),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('From'),
                        DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
