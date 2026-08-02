<?php

namespace App\Filament\Resources\Vouchers\Tables;

use App\Models\Plan;
use App\Models\RadCheck;
use App\Models\RadReply;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->fontFamily('mono')
                    ->weight('bold'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function ($record): string {
                        if ($record->used_count >= $record->max_uses) {
                            return 'Redeemed';
                        }
                        // Creator-based vouchers: validity tied to creator's plan
                        if ($record->created_by) {
                            $creator = $record->creator;
                            if ($creator && $creator->plan_expiry && $creator->plan_expiry->isPast()) {
                                return 'Expired';
                            }
                        } elseif ($record->expires_at && $record->expires_at->isPast()) {
                            return 'Expired';
                        }
                        return 'Active';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Active'   => 'success',
                        'Redeemed' => 'info',
                        'Expired'  => 'danger',
                        default    => 'gray',
                    }),

                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->badge()
                    ->color('info')
                    ->placeholder('Custom')
                    ->sortable(),

                TextColumn::make('label')
                    ->label('Label')
                    ->placeholder('—')
                    ->limit(25)
                    ->searchable(),

                TextColumn::make('data')
                    ->label('Data')
                    ->badge()
                    ->getStateUsing(function ($record): string {
                        if ($record->is_unlimited) {
                            return 'Unlimited';
                        }
                        if ($record->data_limit_mb) {
                            return $record->data_limit_mb >= 1024
                                ? round($record->data_limit_mb / 1024, 1) . ' GB'
                                : $record->data_limit_mb . ' MB';
                        }
                        if ($record->plan) {
                            return $record->plan->limit_unit === 'Unlimited'
                                ? 'Unlimited'
                                : ($record->plan->data_limit . ' ' . $record->plan->limit_unit);
                        }
                        return '—';
                    })
                    ->color(fn (string $state): string => $state === 'Unlimited' ? 'warning' : 'gray'),

                TextColumn::make('usage')
                    ->label('Uses')
                    ->getStateUsing(fn ($record): string => $record->used_count . ' / ' . $record->max_uses),

                TextColumn::make('duration_hours')
                    ->label('Duration')
                    ->getStateUsing(function ($record): string {
                        if (! $record->duration_hours) {
                            return '—';
                        }
                        $days = round($record->duration_hours / 24);
                        return $days . ($days === 1 ? ' day' : ' days');
                    }),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->since()
                    ->placeholder('On first use')
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active'   => 'Active',
                        'redeemed' => 'Redeemed',
                        'expired'  => 'Expired',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'active'   => $query->where(function ($q) {
                                $q->whereColumn('used_count', '<', 'max_uses')
                                  ->where(fn ($q2) => $q2->whereNull('expires_at')
                                                         ->orWhere('expires_at', '>', now()));
                            }),
                            'redeemed' => $query->whereColumn('used_count', '>=', 'max_uses'),
                            'expired'  => $query->where('expires_at', '<=', now()),
                            default    => $query,
                        };
                    }),

                SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->options(Plan::pluck('name', 'id'))
                    ->placeholder('All plans'),

                SelectFilter::make('is_unlimited')
                    ->label('Data Type')
                    ->options([
                        '1' => 'Unlimited only',
                        '0' => 'Capped only',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('resync_radius')
                    ->label('Resync RADIUS')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Resync RADIUS for this voucher?')
                    ->modalDescription('This updates the Simultaneous-Use limit in FreeRADIUS to match the voucher\'s current max_uses. Use this after changing max_uses if devices are being rejected.')
                    ->action(function ($record): void {
                        try {
                            // Ensure Cleartext-Password exists
                            RadCheck::updateOrCreate(
                                ['username' => $record->code, 'attribute' => 'Cleartext-Password'],
                                ['op' => ':=', 'value' => $record->code]
                            );

                            // Update Simultaneous-Use to match current max_uses
                            RadCheck::updateOrCreate(
                                ['username' => $record->code, 'attribute' => 'Simultaneous-Use'],
                                ['op' => ':=', 'value' => (string) $record->max_uses]
                            );

                            // Move Mikrotik-Total-Limit from radcheck → radreply if it was set wrong
                            $staleCheckLimit = RadCheck::where('username', $record->code)
                                ->where('attribute', 'Mikrotik-Total-Limit')
                                ->first();

                            if ($staleCheckLimit) {
                                RadReply::updateOrCreate(
                                    ['username' => $record->code, 'attribute' => 'Mikrotik-Total-Limit'],
                                    ['op' => ':=', 'value' => $staleCheckLimit->value]
                                );
                                $staleCheckLimit->delete();
                            }

                            // Sync expiry if the voucher has an active expires_at
                            if ($record->expires_at && $record->expires_at->isFuture()) {
                                RadCheck::updateOrCreate(
                                    ['username' => $record->code, 'attribute' => 'Expiration'],
                                    ['op' => ':=', 'value' => $record->expires_at->format('d M Y H:i')]
                                );
                            }
                        } catch (\Throwable $e) {
                            Log::error('Voucher RADIUS resync failed', ['code' => $record->code, 'error' => $e->getMessage()]);
                            \Filament\Notifications\Notification::make()
                                ->title('RADIUS resync failed — check logs')
                                ->danger()
                                ->send();
                            return;
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('RADIUS resynced for ' . $record->code)
                            ->body('Simultaneous-Use set to ' . $record->max_uses . '. Devices can reconnect.')
                            ->success()
                            ->send();
                    }),
                Action::make('reset_voucher')
                    ->label('Reset')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Reset this voucher?')
                    ->modalDescription('Sets used_count back to 0 and clears the expiry so the code can be used again. Also closes any stale RADIUS sessions for this voucher.')
                    ->visible(fn ($record) => $record->used_count > 0)
                    ->action(function ($record): void {
                        $updates = ['used_count' => 0];
                        // Clear auto-set expiry (set by consume() from duration_hours) but leave
                        // manually pre-set expires_at alone — we can't tell them apart, but if
                        // duration_hours is set the expiry will be recalculated on next use anyway.
                        if ($record->duration_hours) {
                            $updates['expires_at'] = null;
                        }
                        $record->update($updates);

                        // Close any stale open RADIUS sessions so Simultaneous-Use doesn't block the next attempt
                        DB::table('radacct')
                            ->where('username', 'vch_' . strtolower($record->code))
                            ->whereNull('acctstoptime')
                            ->update([
                                'acctstoptime'        => now(),
                                'acctterminatecause'  => 'Admin-Reset',
                            ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Voucher ' . $record->code . ' reset')
                            ->body('Slot freed and stale sessions cleared. The code can be used again.')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()->label('Revoke'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Revoke Selected'),
                ]),
            ]);
    }
}
