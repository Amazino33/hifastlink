<?php

namespace App\Filament\Resources\Vouchers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VoucherInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Voucher')
                    ->schema([
                        TextEntry::make('code')
                            ->label('Code')
                            ->copyable()
                            ->fontFamily('mono')
                            ->weight('bold'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->getStateUsing(function ($record): string {
                                if ($record->expires_at && $record->expires_at->isPast()) {
                                    return 'Expired';
                                }
                                if ($record->used_count >= $record->max_uses) {
                                    return 'Redeemed';
                                }
                                return 'Active';
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'Active'   => 'success',
                                'Redeemed' => 'info',
                                'Expired'  => 'danger',
                                default    => 'gray',
                            }),

                        TextEntry::make('label')
                            ->label('Label')
                            ->placeholder('No label'),

                        TextEntry::make('plan.name')
                            ->label('Linked Plan')
                            ->badge()
                            ->color('info')
                            ->placeholder('Custom (no plan)'),

                        TextEntry::make('creator.name')
                            ->label('Created By')
                            ->placeholder('—'),

                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('d M Y, g:i A'),
                    ])
                    ->columns(3),

                Section::make('Access Settings')
                    ->schema([
                        TextEntry::make('duration_hours')
                            ->label('Validity')
                            ->getStateUsing(function ($record): string {
                                if (! $record->duration_hours) {
                                    return '—';
                                }
                                $days = round($record->duration_hours / 24);
                                return $days . ($days === 1 ? ' day' : ' days') . " ({$record->duration_hours} h)";
                            }),

                        TextEntry::make('data')
                            ->label('Data Allowance')
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

                        TextEntry::make('speed')
                            ->label('Speed Limits')
                            ->getStateUsing(function ($record): string {
                                if (! $record->speed_limit_download && ! $record->speed_limit_upload) {
                                    return 'No limit';
                                }
                                $dl = $record->speed_limit_download ? $record->speed_limit_download . ' Kbps' : '—';
                                $ul = $record->speed_limit_upload ? $record->speed_limit_upload . ' Kbps' : '—';
                                return "↓ {$dl} / ↑ {$ul}";
                            }),

                        TextEntry::make('max_uses')
                            ->label('Max Uses per Code'),

                        TextEntry::make('expires_at')
                            ->label('Expires At')
                            ->dateTime('d M Y, g:i A')
                            ->placeholder('On first use (clock starts at redemption)'),

                        TextEntry::make('router.name')
                            ->label('Router')
                            ->placeholder('Any router'),
                    ])
                    ->columns(3),

                Section::make('Redemption')
                    ->schema([
                        TextEntry::make('usage')
                            ->label('Times Used')
                            ->getStateUsing(fn ($record): string => $record->used_count . ' / ' . $record->max_uses),

                        IconEntry::make('is_used')
                            ->label('Fully Redeemed')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('gray'),

                        TextEntry::make('user.name')
                            ->label('Last Redeemed By')
                            ->placeholder('Not yet redeemed'),

                        TextEntry::make('used_at')
                            ->label('Last Redeemed At')
                            ->dateTime('d M Y, g:i A')
                            ->placeholder('Not yet redeemed'),
                    ])
                    ->columns(2),
            ]);
    }
}
