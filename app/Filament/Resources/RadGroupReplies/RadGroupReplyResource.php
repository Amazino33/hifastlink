<?php

namespace App\Filament\Resources\RadGroupReplies;

use App\Filament\Resources\RadGroupReplies\Pages\ManageRadGroupReplies;
use App\Models\RadGroupReply;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class RadGroupReplyResource extends Resource
{
    protected static ?string $model = RadGroupReply::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationLabel = 'Plan Definition';

    protected static string|UnitEnum|null $navigationGroup = 'Network Management';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('groupname')
                    ->label('Plan Name (Must match your Plans)')
                    ->required()
                    ->placeholder('e.g., Daily-100MB'),
                
                Select::make('attribute')
                    ->label('Limit Type')
                    ->options([
                        'Mikrotik-Total-Limit' => 'Total Data (Bytes)',
                        'Mikrotik-Rate-Limit' => 'Speed Limit (rx/tx)',
                        'Session-Timeout' => 'Time Limit (Seconds)',
                        'Login-Time' => 'Login Time Restriction',
                        'Acct-Interim-Interval' => 'Validity Interval (Seconds)',
                    ]) 
                    ->required(),

                TextInput::make('value')
                    ->label('Value')
                    ->required()
                    ->placeholder('e.g., 104857600 for 100MB'),
                
                Hidden::make('op')->default(':=')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('groupname')
                    ->label('Plan Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('attribute')
                    ->label('Limit Type')
                    ->searchable(),
                TextColumn::make('value')
                    ->label('Value')
                    ->searchable(),
                TextColumn::make('op')
                    ->label('Operator')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            
            ->filters([
                //
            ])
            ->recordActions([
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
            'index' => ManageRadGroupReplies::route('/'),
        ];
    }
}
 