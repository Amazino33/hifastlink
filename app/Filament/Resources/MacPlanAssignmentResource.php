<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MacPlanAssignmentResource\Pages;
use App\Models\MacPlanAssignment;
use App\Models\Router;
use App\Models\Plan;
use Filament\Actions\BulkActionGroup as ActionsBulkActionGroup;
use Filament\Actions\DeleteAction as ActionsDeleteAction;
use Filament\Actions\DeleteBulkAction as ActionsDeleteBulkAction;
use Filament\Actions\EditAction as ActionsEditAction;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class MacPlanAssignmentResource extends Resource
{
    protected static ?string $model = MacPlanAssignment::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationLabel = 'NAS Plan Assignments';
    protected static \UnitEnum|string|null $navigationGroup = 'Network Management';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('router_id')
                    ->label('Router')
                    ->options(Router::pluck('name', 'id'))
                    ->required(),

                TextInput::make('nas_identifier')
                    ->label('NAS Identifier')
                    ->required()
                    ->placeholder('e.g., router-001, mikrotik-main'),

                Select::make('plan_id')
                    ->label('Data Plan')
                    ->options(Plan::where('is_custom', true)->pluck('name', 'id'))
                    ->required(),

                TextInput::make('device_name')
                    ->label('Device Name'),

                Textarea::make('notes')
                    ->label('Notes'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nas_identifier')
                    ->label('NAS Identifier')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('router.name')
                    ->label('Router')
                    ->searchable()
                    ->badge(),

                TextColumn::make('dataPlan.name')
                    ->label('Data Plan')
                    ->searchable(),

                TextColumn::make('device_name')
                    ->label('Device Name')
                    ->placeholder('Not specified'),

                BadgeColumn::make('is_active')
                    ->label('Status')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ])
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('router_id')
                    ->label('Router')
                    ->options(Router::pluck('name', 'id')),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        true => 'Active',
                        false => 'Inactive',
                    ]),
            ])
            ->actions([
                ActionsEditAction::make(),
                ActionsDeleteAction::make(),
            ])
            ->bulkActions([
                ActionsBulkActionGroup::make([
                    ActionsDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMacPlanAssignments::route('/'),
            'create' => Pages\CreateMacPlanAssignment::route('/create'),
            'edit' => Pages\EditMacPlanAssignment::route('/{record}/edit'),
        ];
    }
}