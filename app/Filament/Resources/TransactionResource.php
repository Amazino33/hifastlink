<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use BackedEnum;
use Filament\Schemas\Schema;
use UnitEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Traits\Macroable;

class TransactionResource extends Resource
{
    use Macroable {
        Macroable::__call as dynamicMacroCall;
    }

    protected static bool $isDiscovered = true;

    /**
     * @var class-string<Model>|null
     */
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

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
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
    public static function configureTable(Table $table): void
    {
        $table
            ->modelLabel(static::getModelLabel(...))
            ->pluralModelLabel(static::getPluralModelLabel(...))
            ->recordTitleAttribute(static::getRecordTitleAttribute(...))
            ->recordTitle(static::getRecordTitle(...))
            ->authorizeReorder(static::canReorder(...));

        static::table($table); /** @phpstan-ignore staticMethod.resultUnused */
    }

    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel()::query();

        if (! static::isScopedToTenant()) {
            $panel = Filament::getCurrentOrDefaultPanel();

            if ($panel?->hasTenancy()) {
                $query->withoutGlobalScope($panel->getTenancyScopeName());
            }
        }

        return $query;
    }

    /**
     * @return class-string<Model>
     */
    public static function getModel(): string
    {
        return static::$model ?? (string) str(class_basename(static::class))
            ->beforeLast('Resource')
            ->prepend(app()->getNamespace() . 'Models\\');
    }

    /**
     * @return array<class-string<RelationManager> | RelationGroup | RelationManagerConfiguration>
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<class-string<Widget>>
     */
    public static function getWidgets(): array
    {
        return [];
    }

    public static function isEmailVerificationRequired(Panel $panel): bool
    {
        return $panel->isEmailVerificationRequired();
    }

    public static function isDiscovered(): bool
    {
        return static::$isDiscovered;
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
