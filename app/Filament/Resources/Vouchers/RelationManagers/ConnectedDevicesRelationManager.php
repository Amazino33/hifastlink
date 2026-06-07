<?php

namespace App\Filament\Resources\Vouchers\RelationManagers;

use App\Models\Device;
use App\Models\RadAcct;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class ConnectedDevicesRelationManager extends RelationManager
{
    protected static string $relationship = 'connectedDevices';

    protected static ?string $title = 'Connected Devices';

    public function table(Table $table): Table
    {
        // ->query() is checked before getRelationshipQuery() in Filament's HasQuery::getQuery(),
        // so this bypasses the stub hasMany on Voucher and uses the JSON-path filter directly.
        /** @var \App\Models\Voucher $owner */
        $owner = $this->getOwnerRecord();

        return $table
            ->query(fn (): Builder => Device::query()
                ->whereNull('user_id')
                ->where('meta->voucher_code', $owner->code)
                ->orderByDesc('last_seen'))
            ->columns([
                TextColumn::make('mac')
                    ->label('MAC Address')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Copied!'),

                TextColumn::make('ip')
                    ->label('IP Address')
                    ->fontFamily('mono')
                    ->placeholder('—'),

                TextColumn::make('last_seen')
                    ->label('Last Seen')
                    ->since()
                    ->sortable(),

                TextColumn::make('first_seen')
                    ->label('First Seen')
                    ->dateTime('d M Y, g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_connected')
                    ->label('Connected')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('router.name')
                    ->label('Router')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('remove')
                    ->label('Remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove this device?')
                    ->modalDescription('The device will be unregistered from this voucher and its slot freed. It will need to re-enter the voucher code to reconnect. Active sessions may stay online until they time out naturally.')
                    ->action(function (Device $record): void {
                        /** @var \App\Models\Voucher $voucher */
                        $voucher = $this->getOwnerRecord();
                        $mac     = $record->mac;

                        $record->delete();

                        if ($voucher->used_count > 0) {
                            $voucher->decrement('used_count');
                        }

                        // Close open radacct sessions for this MAC so FreeRADIUS's
                        // Simultaneous-Use count drops immediately.
                        // MikroTik stores MACs as AA:BB:CC or AA-BB-CC; normalise both.
                        try {
                            $normalMac = strtoupper(str_replace('-', ':', $mac));
                            RadAcct::where('callingstationid', $normalMac)
                                ->orWhere('callingstationid', strtolower($normalMac))
                                ->whereNull('acctstoptime')
                                ->update([
                                    'acctstoptime'       => now(),
                                    'acctterminatecause' => 'Admin-Reset',
                                ]);
                        } catch (\Throwable $e) {
                            Log::warning('Could not close radacct sessions for ' . $mac . ': ' . $e->getMessage());
                        }

                        Notification::make()
                            ->title('Device removed')
                            ->body($mac . ' unregistered. Slot freed.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No devices registered')
            ->emptyStateDescription('Devices appear here after entering this voucher code on the captive portal login page.')
            ->emptyStateIcon('heroicon-o-device-phone-mobile');
    }
}
