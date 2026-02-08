<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RouterResource\Pages;
use App\Models\Router;
use BackedEnum;
use Filament\Actions\Action as ActionsAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Number;
use UnitEnum;

class RouterResource extends Resource
{
    protected static ?string $model = Router::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationLabel = 'Routers';
    protected static string|null|UnitEnum $navigationGroup = 'Network Management';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Router Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Uyo Hub'),
                        
                        Forms\Components\TextInput::make('location')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Address or site location'),
                        
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->placeholder('Additional details about this router'),
                    ])->columns(1),

            Section::make('Network Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP Address')
                            ->required()
                            ->ip()
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., 192.168.1.1'),
                        
                        Forms\Components\TextInput::make('nas_identifier')
                            ->label('NAS Identifier')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., router_uyo_01')
                            ->helperText('Unique identifier for RADIUS'),
                        
                        Forms\Components\TextInput::make('secret')
                            ->label('RADIUS Secret')
                            ->required()
                            ->password()
                            ->revealable()
                            ->placeholder('Shared secret for RADIUS auth'),
                    ])->columns(2),

                Section::make('MikroTik API Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('api_user')
                            ->label('API Username')
                            ->placeholder('admin'),
                        
                        Forms\Components\TextInput::make('api_password')
                            ->label('API Password')
                            ->password()
                            ->revealable(),
                        
                        Forms\Components\TextInput::make('api_port')
                            ->label('API Port')
                            ->numeric()
                            ->default(8728)
                            ->placeholder('8728'),
                    ])->columns(3),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Disable to stop accepting connections from this router'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('nas_identifier')
                    ->label('NAS ID')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('active_users_count')
                    ->label('Active Users')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => $state ?? 0),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn ($record) => $record->is_online ? 'Online' : 'Offline')
                    ->colors([
                        'success' => 'Online',
                        'danger' => 'Offline',
                    ])
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All routers')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->recordActions([
                ActionsAction::make('download_config')
                    ->label('Download Config (.rsc)')
                    ->icon('heroicon-o-cloud-download')
                    ->url(fn (Router $record) => route('router.download', $record))
                    ->openUrlInNewTab(false),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRouters::route('/'),
            'create' => Pages\CreateRouter::route('/create'),
            'edit' => Pages\EditRouter::route('/{record}/edit'),
        ];
    }
}
