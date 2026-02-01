<?php

namespace App\Filament\Resources;

use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Actions\DeleteAction;
use App\Models\RadCheck;
use App\Models\RadReply;
use App\Models\RadAcct;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Fieldset;
use Illuminate\Support\Number;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\UserResource\Pages;
use Filament\Actions\DeleteAction as ActionsDeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset as ComponentsFieldset;
use Filament\Schemas\Schema;

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
                    ->getStateUsing(function ($record) {
                        return RadAcct::where('username', $record->username)
                            ->sum(DB::raw('acctinputoctets + acctoutputoctets'));
                    })
                    ->formatStateUsing(fn (?int $state): string => Number::fileSize($state ?? 0)),

                IconColumn::make('online_status')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('plan.name')
                    ->label('Current Plan')
                    ->badge()
                    ->color('info')
                    ->sortable(),

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
            ->defaultSort('name')
            ->actions([
                ActionsDeleteAction::make()
                    ->before(function (ActionsDeleteAction $action, $record) {
                        // Delete from RADIUS before deleting user
                        RadCheck::where('username', $record->username)->delete();
                        RadReply::where('username', $record->username)->delete();
                        // Note: RadAcct is kept for historical records
                    }),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                ComponentsFieldset::make('Basic Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('username')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->label('RADIUS Username'),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('password')
                            ->password()
                            ->required(fn ($context) => $context === 'create')
                            ->minLength(8)
                            ->confirmed()
                            ->dehydrated(fn ($state) => filled($state)),
                        TextInput::make('password_confirmation')
                            ->password()
                            ->required(fn ($context) => $context === 'create')
                            ->minLength(8),
                        TextInput::make('radius_password')
                            ->label('RADIUS Password')
                            ->password()
                            ->helperText('Plain-text password used by RADIUS; leave blank to keep existing')
                            ->dehydrated(fn ($state) => filled($state)),
                    ])->columns(2),

                ComponentsFieldset::make('Plan & Subscription')
                    ->schema([
                        Select::make('plan_id')
                            ->relationship('plan', 'name')
                            ->label('Assign Plan')
                            ->searchable()
                            ->preload()
                            ->afterStateUpdated(function ($state, $set) {
                                // Placeholder: logic to reset expiry will go here later
                            }),
                        DateTimePicker::make('plan_expiry')
                            ->label('Plan Expiry')
                            ->default(now()->addDays(30)),
                        DateTimePicker::make('plan_started_at')
                            ->label('Plan Started At')
                            ->default(now()),
                    ])->columns(3),

                ComponentsFieldset::make('Family Settings')
                    ->schema([
                        Select::make('parent_id')
                            ->relationship('parent', 'name')
                            ->label('Parent User')
                            ->searchable()
                            ->preload()
                            ->helperText('Leave blank if this is a family admin'),
                        Toggle::make('is_family_admin')
                            ->label('Is Family Admin')
                            ->helperText('Can manage family members'),
                        TextInput::make('family_limit')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Maximum number of family members'),
                    ])->columns(3),
            ]);
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
