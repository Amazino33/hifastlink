<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomPlanRequestResource\Pages;
use App\Models\CustomPlanRequest;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use UnitEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Panel;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Traits\Macroable;

class CustomPlanRequestResource extends Resource
{
    use Macroable {
        Macroable::__call as dynamicMacroCall;
    }

    protected static bool $isDiscovered = true;

    /**
     * @var class-string<Model>|null
     */
    protected static ?string $model = CustomPlanRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Plans & Billing';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Request Details')
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->disabled(),

                        Select::make('router_id')
                            ->relationship('router', 'name')
                            ->required()
                            ->disabled(),

                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state === 'approved' || $state === 'rejected') {
                                    $set('reviewed_at', now());
                                }
                            }),

                        Toggle::make('show_universal_plans')
                            ->label('Show Universal Plans')
                            ->helperText('Allow universal plans to be visible alongside custom plans'),

                        KeyValue::make('requested_plans')
                            ->label('Requested Plans')
                            ->disabled()
                            ->columnSpanFull()
                            ->formatStateUsing(function ($state) {
                                if (!is_array($state)) {
                                    return $state;
                                }

                                $formatted = [];
                                foreach ($state as $planIndex => $plan) {
                                    $formattedPlan = [];
                                    foreach ($plan as $key => $value) {
                                        $label = match($key) {
                                            'name' => 'Plan Name',
                                            'description' => 'Description',
                                            'data_limit' => 'Data Limit (MB)',
                                            'time_limit' => 'Time Limit (Hours)',
                                            'speed_limit_upload' => 'Upload Speed (Mbps)',
                                            'speed_limit_download' => 'Download Speed (Mbps)',
                                            'validity_days' => 'Validity Days',
                                            'price' => 'Price (₦)',
                                            'speed_limit' => 'Speed Limit',
                                            'allowed_login_time' => 'Allowed Login Time',
                                            'limit_unit' => 'Limit Unit',
                                            'max_devices' => 'Max Simultaneous Devices',
                                            'features' => 'Features',
                                            default => ucwords(str_replace('_', ' ', $key))
                                        };

                                        if ($key === 'allowed_login_time') {
                                            $timeOptions = [
                                                'Al2300-0600' => 'Night Plan (11:00 PM - 6:00 AM)',
                                                'Al0000-0500' => 'Midnight Owl (12:00 AM - 5:00 AM)',
                                                'SaSu0000-2400' => 'Weekend Only (Sat & Sun)',
                                                'Wk0800-1700' => 'Work Hours (Mon-Fri, 8 AM - 5 PM)',
                                                'Al0800-1800' => 'Daytime Only (8 AM - 6 PM)',
                                            ];
                                            $formattedPlan[$label] = $timeOptions[$value] ?? ($value ?: '24/7 Access');
                                        } elseif ($value === null || $value === '') {
                                            $formattedPlan[$label] = 'Not specified';
                                        } else {
                                            $formattedPlan[$label] = $value;
                                        }
                                    }
                                    $formatted["Plan " . ($planIndex + 1)] = $formattedPlan;
                                }

                                return $formatted;
                            }),
                    ]),

                Section::make('Review')
                    ->schema([
                        Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->rows(3),

                        DateTimePicker::make('reviewed_at')
                            ->label('Reviewed At')
                            ->disabled(),

                        Select::make('reviewed_by')
                            ->relationship('reviewer', 'name')
                            ->label('Reviewed By')
                            ->disabled(),
                    ])
                    ->hidden(fn ($get) => $get('status') === 'pending'),
            ]);
    }

    public static function Table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Requester')
                    ->sortable(),

                TextColumn::make('router.name')
                    ->label('Router')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),

                IconColumn::make('show_universal_plans')
                    ->label('Show Universal')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Requested At')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('reviewed_at')
                    ->label('Reviewed At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not reviewed'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Custom Plan Request')
                    ->modalDescription('This will create the custom data plans for the router. Are you sure?')
                    ->modalSubmitActionLabel('Yes, Approve')
                    ->action(function (CustomPlanRequest $record) {
                        $record->update([
                            'status' => 'approved',
                            'reviewed_at' => now(),
                            'reviewed_by' => auth()->id(),
                        ]);

                        // Create the custom plans
                        self::createCustomPlans($record);
                    })
                    ->visible(fn (CustomPlanRequest $record) => $record->isPending()),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Custom Plan Request')
                    ->modalDescription('This will reject the custom plan request. Are you sure?')
                    ->modalSubmitActionLabel('Yes, Reject')
                    ->action(function (CustomPlanRequest $record) {
                        $record->update([
                            'status' => 'rejected',
                            'reviewed_at' => now(),
                            'reviewed_by' => auth()->id(),
                        ]);
                    })
                    ->visible(fn (CustomPlanRequest $record) => $record->isPending()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomPlanRequests::route('/'),
            'create' => Pages\CreateCustomPlanRequest::route('/create'),
            'edit' => Pages\EditCustomPlanRequest::route('/{record}/edit'),
        ];
    }

    protected static function createCustomPlans(CustomPlanRequest $request): void
    {
        foreach ($request->requested_plans as $planData) {
            // Convert MB to bytes (1 MB = 1,048,576 bytes)
            $dataLimitInBytes = ($planData['data_limit'] ?? 0) * 1048576;

            \App\Models\Plan::create([
                'name' => $planData['name'],
                'description' => $planData['description'] ?? null,
                'price' => $planData['price'] ?? 0,
                'data_limit' => $dataLimitInBytes,
                'time_limit' => $planData['time_limit'] ?? null,
                'speed_limit_upload' => $planData['speed_limit_upload'] ?? null,
                'speed_limit_download' => $planData['speed_limit_download'] ?? null,
                'validity_days' => $planData['validity_days'] ?? 30,
                'speed_limit' => $planData['speed_limit'] ?? '10M/10M',
                'allowed_login_time' => $planData['allowed_login_time'] ?? null,
                'limit_unit' => $planData['limit_unit'] ?? 'MB',
                'max_devices' => $planData['max_devices'] ?? null,
                'features' => $planData['features'] ?? null,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 0,
                'router_id' => $request->router_id,
                'is_custom' => true,
                'show_universal_plans' => $request->show_universal_plans,
            ]);
        }
    }
}