<?php

namespace App\Filament\Resources\VoucherResource\Pages;

use App\Filament\Resources\VoucherResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use App\Models\Plan;
use App\Models\Voucher;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class ListVouchers extends ListRecords
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate Batch')
                ->icon('heroicon-o-ticket')
                ->form([
                    Select::make('plan_id')
                        ->label('Select Plan')
                        ->options(Plan::all()->pluck('name', 'id'))
                        ->required(),
                    TextInput::make('quantity')
                        ->numeric()
                        ->default(10)
                        ->minValue(1)
                        ->maxValue(100)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $planId = $data['plan_id'];
                    $qty = $data['quantity'];
                    
                    for ($i = 0; $i < $qty; $i++) {
                        Voucher::create([
                            'code' => strtoupper(Str::random(4)) . '-' . rand(1000, 9999),
                            'plan_id' => $planId,
                        ]);
                    }
                    
                    Notification::make()
                        ->title('Success')
                        ->body("Generated $qty vouchers successfully.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}