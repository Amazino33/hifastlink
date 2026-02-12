<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Actions\DeleteAction;
use App\Models\RadCheck;
use App\Models\RadReply;
use App\Models\RadAcct;
use Filament\Actions\DeleteAction as ActionsDeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Form;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset as ComponentsFieldset;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

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

                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color('primary')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList(),
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
                ComponentsFieldset::make('Account Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(1),
                        TextInput::make('username')
                            ->label('Username (RADIUS)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Used for authentication')
                            ->columnSpan(1),
                        TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(20)
                            ->columnSpan(1),
                        Select::make('roles')
                            ->options(\Spatie\Permission\Models\Role::pluck('name', 'name'))
                            ->label('Roles')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->default(fn ($record) => $record ? $record->roles->pluck('name')->toArray() : [])
                            ->columnSpan(1),
                    ])->columns(2),

                ComponentsFieldset::make('Security')
                    ->schema([
                        TextInput::make('password')
                            ->label('Login Password')
                            ->password()
                            ->required(fn ($context) => $context === 'create')
                            ->minLength(8)
                            ->confirmed()
                            ->dehydrated(fn ($state) => filled($state))
                            ->columnSpan(1),
                        TextInput::make('password_confirmation')
                            ->label('Confirm Password')
                            ->password()
                            ->required(fn ($context) => $context === 'create')
                            ->minLength(8)
                            ->columnSpan(1),
                        TextInput::make('radius_password')
                            ->label('RADIUS Password')
                            ->password()
                            ->helperText('Plain-text password for RADIUS; leave blank to keep existing')
                            ->dehydrated(fn ($state) => filled($state))
                            ->columnSpan(1),
                    ])->columns(2),

                ComponentsFieldset::make('Subscription Details')
                    ->schema([
                        Select::make('plan_id')
                            ->relationship('plan', 'name')
                            ->label('Assigned Plan')
                            ->searchable()
                            ->preload()
                            ->columnSpan(2),
                        DateTimePicker::make('plan_started_at')
                            ->label('Plan Start Date')
                            ->default(now())
                            ->columnSpan(1),
                        DateTimePicker::make('plan_expiry')
                            ->label('Plan Expiry Date')
                            ->default(now()->addDays(30))
                            ->columnSpan(1),
                    ])->columns(2),

                ComponentsFieldset::make('Family Plan Settings')
                    ->schema([
                        Toggle::make('is_family_admin')
                            ->label('Family Plan Administrator')
                            ->helperText('Can manage family members and shared data')
                            ->inline(false)
                            ->columnSpan(2),
                        Select::make('parent_id')
                            ->relationship('parent', 'name')
                            ->label('Parent Account')
                            ->searchable()
                            ->preload()
                            ->helperText('Leave blank if this is a family admin')
                            ->columnSpan(1),
                        TextInput::make('family_limit')
                            ->label('Family Members Limit')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Maximum family members')
                            ->columnSpan(1),
                    ])->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            // Minimal page registration. These classes live in
            // App\Filament\Resources\UserResource\Pages\
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
