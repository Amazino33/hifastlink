<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RouterResource\Pages;
use App\Models\Router;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use UnitEnum;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section as ComponentsSection;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Traits\Macroable;

class RouterResource extends Resource
{
    use Macroable {
        Macroable::__call as dynamicMacroCall;
    }

    protected static bool $isDiscovered = true;
    protected static ?string $model = Router::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationLabel = 'Routers';
    protected static string|UnitEnum|null $navigationGroup = 'Network Management';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                ComponentsSection::make('Router Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Uyo Hub'),
                        
                        TextInput::make('location')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Address or site location'),
                        
                        Textarea::make('description')
                            ->rows(2)
                            ->placeholder('Additional details about this router'),
                    ])->columns(1),

                ComponentsSection::make('Network Configuration')
                    ->schema([
                        TextInput::make('ip_address')
                            ->label('Router LAN IP')
                            ->required()
                            ->ip()
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., 192.168.88.1')
                            ->helperText('The router\'s local network IP address'),
                        
                        TextInput::make('vpn_ip')
                            ->label('VPN IP Address')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-assigned on save')
                            ->helperText('Automatically assigned from VPN pool')
                            ->visible(fn ($record) => $record !== null),
                        
                        TextInput::make('nas_identifier')
                            ->label('NAS Identifier')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., router_uyo_01')
                            ->helperText('Unique identifier for RADIUS (no spaces)'),
                        
                        TextInput::make('secret')
                            ->label('RADIUS Secret')
                            ->required()
                            ->password()
                            ->revealable()
                            ->placeholder('Shared secret for RADIUS authentication')
                            ->default(fn () => \Illuminate\Support\Str::random(16)),
                    ])->columns(2),

                ComponentsSection::make('MikroTik API Configuration')
                    ->schema([
                        TextInput::make('api_user')
                            ->label('API Username')
                            ->placeholder('admin')
                            ->default('admin'),
                        
                        TextInput::make('api_password')
                            ->label('API Password')
                            ->password()
                            ->revealable()
                            ->placeholder('Leave empty to use router default'),
                        
                        TextInput::make('api_port')
                            ->label('API Port')
                            ->numeric()
                            ->default(8728)
                            ->placeholder('8728'),
                    ])->columns(3)
                    ->collapsible(),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Disable to stop accepting connections from this router'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                TextColumn::make('location')
                    ->searchable()
                    ->limit(30),
                
                TextColumn::make('vpn_ip')
                    ->label('VPN IP')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),
                
                TextColumn::make('ip_address')
                    ->label('LAN IP')
                    ->searchable()
                    ->toggleable(),
                
                TextColumn::make('nas_identifier')
                    ->label('NAS ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                
                TextColumn::make('active_users_count')
                    ->label('Active Users')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => $state ?? 0)
                    ->toggleable(),
                
                BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn ($record) => $record->is_online ? 'Online' : 'Offline')
                    ->colors([
                        'success' => 'Online',
                        'danger' => 'Offline',
                    ])
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        true => 'Active',
                        false => 'Inactive',
                    ]),
            ])
            ->actions([
                Action::make('download_config')
                    ->label('Download Config')
                    ->icon('heroicon-m-cloud-arrow-down')
                    ->color('success')
                    ->url(fn (Router $record) => route('router.download', $record))
                    ->openUrlInNewTab(false),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
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