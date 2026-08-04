<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RouterPayoutResource\Pages;
use App\Models\Router;
use App\Models\RouterPayout;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Traits\Macroable;
use UnitEnum;

class RouterPayoutResource extends Resource
{
    use Macroable {
        Macroable::__call as dynamicMacroCall;
    }

    protected static bool $isDiscovered = true;

    protected static ?string $model = RouterPayout::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Router Payouts';

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Payout Details')
                    ->schema([
                        Select::make('router_id')
                            ->label('Router')
                            ->options(
                                Router::with('owner')
                                    ->whereNotNull('owner_id')
                                    ->get()
                                    ->mapWithKeys(fn ($r) => [
                                        $r->id => $r->name . ' — ' . ($r->owner->name ?? $r->owner->phone ?? 'Unknown owner'),
                                    ])
                            )
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn ($state, $set, $get) => static::recalculate($state, $get('period_start'), $get('period_end'), $set)),

                        DatePicker::make('period_start')
                            ->label('Period Start')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, $set, $get) => static::recalculate($get('router_id'), $state, $get('period_end'), $set)),

                        DatePicker::make('period_end')
                            ->label('Period End')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, $set, $get) => static::recalculate($get('router_id'), $get('period_start'), $state, $set)),

                        TextInput::make('amount')
                            ->label('Payout Amount (₦)')
                            ->numeric()
                            ->prefix('₦')
                            ->required()
                            ->helperText('Auto-filled from completed Paystack transactions in the period. You can override it.'),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'paid'    => 'Paid',
                            ])
                            ->default('pending')
                            ->required(),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->placeholder('Optional — e.g. transfer reference, bank details used, etc.')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    protected static function recalculate(?string $routerId, ?string $start, ?string $end, callable $set): void
    {
        if ($routerId && $start && $end) {
            $amount = RouterPayout::calculateRevenue((int) $routerId, $start, $end);
            $set('amount', $amount);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('router.name')
                    ->label('Router')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('router.owner.name')
                    ->label('Owner')
                    ->getStateUsing(fn ($record) => $record->router?->owner?->name ?? $record->router?->owner?->phone ?? '—')
                    ->searchable(query: fn ($query, $search) => $query->whereHas('router.owner', fn ($q) => $q->where('name', 'like', "%{$search}%")))
                    ->sortable(false),

                TextColumn::make('period_start')
                    ->label('Period')
                    ->getStateUsing(fn ($record) => $record->period_start->format('M d') . ' – ' . $record->period_end->format('M d, Y'))
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('NGN')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                    ]),

                TextColumn::make('paid_at')
                    ->label('Paid On')
                    ->dateTime('M d, Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid'    => 'Paid',
                    ]),

                SelectFilter::make('router_id')
                    ->label('Router')
                    ->options(Router::pluck('name', 'id'))
                    ->searchable(),
            ])
            ->actions([
                Action::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Mark payout as paid?')
                    ->modalDescription(fn ($record) => "This will mark the ₦" . number_format($record->amount, 2) . " payout for {$record->router?->name} as paid.")
                    ->action(function ($record) {
                        $record->update([
                            'status'  => 'paid',
                            'paid_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Payout marked as paid')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRouterPayouts::route('/'),
            'create' => Pages\CreateRouterPayout::route('/create'),
            'edit'   => Pages\EditRouterPayout::route('/{record}/edit'),
        ];
    }
}
