<?php

namespace App\Filament\Resources\Vouchers\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use App\Models\Plan;

class VouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Voucher code copied to clipboard')
                    ->copyMessageDuration(1500),

                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                IconColumn::make('is_used')
                    ->label('Used')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('user.username')
                    ->label('Used By')
                    ->getStateUsing(fn ($record) => $record->is_used ? $record->user?->username : '-'),

                TextColumn::make('created_at')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->options(Plan::pluck('name', 'id')),

                SelectFilter::make('is_used')
                    ->label('Status')
                    ->options([
                        '0' => 'Unused',
                        '1' => 'Used',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
