<?php

namespace App\Filament\Resources;

use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Resources\Table as ResourceTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Support\Number;
use Illuminate\Support\Carbon;
use App\Filament\Resources\UserResource\Pages;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->label('Radius User')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('data_used')
                    ->label('Data Usage')
                    ->formatStateUsing(fn (?int $state): string => Number::fileSize($state ?? 0)),

                IconColumn::make('online_status')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('plan_expiry')
                    ->label('Plan Expiry')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($state): string => ($state && Carbon::parse($state)->isFuture()) ? 'success' : 'danger'),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            // Minimal page registration. These classes live in
            // App\Filament\Resources\UserResource\Pages\
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
