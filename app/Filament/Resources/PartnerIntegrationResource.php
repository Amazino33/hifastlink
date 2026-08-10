<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerIntegrationResource\Pages;
use App\Models\PartnerIntegration;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Traits\Macroable;
use UnitEnum;

class PartnerIntegrationResource extends Resource
{
    use Macroable {
        Macroable::__call as dynamicMacroCall;
    }

    protected static bool $isDiscovered = true;
    protected static ?string $model = PartnerIntegration::class;
    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-puzzle-piece';
    protected static ?string $navigationLabel = 'Integrations';
    protected static string|UnitEnum|null $navigationGroup   = 'Settings';
    protected static ?int    $navigationSort  = 25;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->hasRole('super_admin'));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Partner Details')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(100)
                        ->placeholder('Brothers Crib')
                        ->helperText('Display name shown on the captive portal page'),

                    TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50)
                        ->placeholder('brotherscrib')
                        ->helperText('URL-safe identifier — players visit /voucher/{slug}')
                        ->rules(['regex:/^[a-z0-9\-]+$/']),
                ])->columns(2),

            Section::make('API Connection')
                ->schema([
                    TextInput::make('api_url')
                        ->label('Redeem URL')
                        ->required()
                        ->url()
                        ->maxLength(255)
                        ->placeholder('https://example.com/api/voucher/redeem')
                        ->helperText('HiFastLink POSTs here with X-API-Key header to validate codes'),

                    TextInput::make('api_key')
                        ->label('API Key')
                        ->required()
                        ->password()
                        ->revealable()
                        ->maxLength(255)
                        ->helperText('Shared secret — must match what the partner configured'),

                    TextInput::make('code_field')
                        ->label('Code Field Name')
                        ->required()
                        ->default('code')
                        ->maxLength(50)
                        ->helperText('The JSON key sent in the POST body, e.g. "code" or "invoice_number"'),
                ])->columns(2),

            Section::make('Captive Portal Appearance')
                ->schema([
                    TextInput::make('portal_title')
                        ->label('Page Title')
                        ->placeholder('Brothers Crib')
                        ->helperText('Large heading on the portal. Defaults to the integration name.'),

                    TextInput::make('portal_subtitle')
                        ->label('Page Subtitle')
                        ->placeholder('Game Shop Wi-Fi Access'),

                    TextInput::make('icon')
                        ->label('FontAwesome Icon')
                        ->default('fa-solid fa-wifi')
                        ->placeholder('fa-solid fa-gamepad')
                        ->helperText('Any FA 6 class — e.g. fa-solid fa-mortar-pestle'),

                    ColorPicker::make('primary_color')
                        ->label('Brand Colour')
                        ->default('#7c3aed')
                        ->helperText('Used for buttons, headings, and the header gradient'),
                ])->columns(2),

            Section::make('Code Input')
                ->schema([
                    TextInput::make('code_label')
                        ->label('Field Label')
                        ->default('Wi-Fi Code')
                        ->maxLength(60),

                    TextInput::make('code_placeholder')
                        ->label('Placeholder Text')
                        ->placeholder('e.g. A3BK7NMQ')
                        ->maxLength(60),

                    TextInput::make('code_maxlength')
                        ->label('Max Length')
                        ->numeric()
                        ->default(20)
                        ->minValue(4)
                        ->maxValue(100),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive integrations reject all redeem attempts'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')
                    ->label('Portal URL')
                    ->formatStateUsing(fn ($state) => "/voucher/{$state}")
                    ->copyable()
                    ->color('gray'),
                TextColumn::make('api_url')->label('API URL')->limit(40)->color('gray'),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('updated_at')->label('Last Updated')->since()->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->emptyStateHeading('No integrations yet')
            ->emptyStateDescription('Click "New Integration" to add your first partner.');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPartnerIntegrations::route('/'),
            'create' => Pages\CreatePartnerIntegration::route('/create'),
            'edit'   => Pages\EditPartnerIntegration::route('/{record}/edit'),
        ];
    }
}
