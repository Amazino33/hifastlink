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
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Panel;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Filament\Schemas\Components\Section as ComponentsSection;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Traits\Macroable;

class RouterResource extends Resource
{
    use Macroable {
        Macroable::__call as dynamicMacroCall;
    }

    protected static bool $isDiscovered = true;

    /**
     * @var class-string<Model>|null
     */
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
                    ->description('Basic identification for this deployment location.')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Uyo Hub Main'),
                        
                        TextInput::make('location')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Address or site location'),
                        
                        Textarea::make('description')
                            ->rows(2)
                            ->placeholder('Additional details about this router'),
                    ])->columns(1),

                ComponentsSection::make('WireGuard & RADIUS Configuration')
                    ->description('Configure the secure VPN tunnel and authentication credentials for this specific router.')
                    ->schema([
                        TextInput::make('ip_address')
                            ->label('WireGuard VPN IP Address')
                            ->required()
                            ->ip()
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., 192.168.42.10')
                            ->helperText('The private static IP assigned to this router inside the VPN tunnel.'),
                        
                        TextInput::make('nas_identifier')
                            ->label('NAS Identifier (Identity)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., router_uyo_01')
                            ->helperText('The unique RouterOS Identity. FreeRADIUS uses this to identify the location.'),
                        
                        TextInput::make('secret')
                            ->label('Unique RADIUS Secret')
                            ->required()
                            ->password()
                            ->revealable()
                            ->placeholder('Enter a strong, unique secret')
                            ->helperText('The unique shared secret for this specific router.'),
                            
                        Toggle::make('vpn_enabled')
                            ->label('Enable WireGuard via Script')
                            ->default(true)
                            ->helperText('If enabled, the generated setup script will configure the WireGuard tunnel automatically.')
                            ->columnSpanFull(),
                    ])->columns(2),

                ComponentsSection::make('MikroTik API Configuration')
                    ->description('Credentials for background communication and speed reporting.')
                    ->schema([
                        TextInput::make('api_user')
                            ->label('API Username')
                            ->placeholder('admin'),
                        
                        TextInput::make('api_password')
                            ->label('API Password')
                            ->password()
                            ->revealable(),
                        
                        TextInput::make('api_port')
                            ->label('API Port')
                            ->numeric()
                            ->default(8728)
                            ->placeholder('8728'),
                    ])->columns(3),

                ComponentsSection::make('Status')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active Configuration')
                            ->default(true)
                            ->helperText('Disable to stop accepting connections from this router'),
                    ]),
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
                
                TextColumn::make('ip_address')
                    ->label('VPN IP Address')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('info'),
                
                TextColumn::make('nas_identifier')
                    ->label('NAS ID')
                    ->searchable()
                    ->toggleable(),
                
                TextColumn::make('active_users_count')
                    ->label('Active Users')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => $state ?? 0),
                
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
                    ->label('Download Config (.rsc)')
                    ->icon('heroicon-m-cloud-arrow-down')
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