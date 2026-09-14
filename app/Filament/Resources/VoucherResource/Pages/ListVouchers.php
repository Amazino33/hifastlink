<?php

namespace App\Filament\Resources\VoucherResource\Pages;

use App\Filament\Resources\VoucherResource;
use App\Models\Plan;
use App\Models\Router;
use App\Services\VoucherGenerationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListVouchers extends ListRecords
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate Batch')
                ->icon('heroicon-o-ticket')
                ->modalHeading('Generate Paid Voucher Batch')
                ->modalDescription('Every voucher batch is financially tracked and backed by prepaid wallet funds or audited override.')
                ->modalSubmitActionLabel('Generate & Pay')
                ->form([
                    Select::make('plan_id')
                        ->label('Select Plan')
                        ->options(function () {
                            return Plan::where('is_active', true)
                                ->get()
                                ->mapWithKeys(fn ($p) => [$p->id => "{$p->name} — ₦" . number_format((float) $p->price, 2) . " ({$p->validity_days} days)"]);
                        })
                        ->required()
                        ->live(),

                    TextInput::make('quantity')
                        ->label('Quantity')
                        ->numeric()
                        ->default(10)
                        ->minValue(1)
                        ->maxValue(200)
                        ->required()
                        ->live(),

                    Select::make('router_id')
                        ->label('Restrict to Router')
                        ->options(Router::where('is_active', true)->pluck('name', 'id'))
                        ->placeholder('Global (All Routers)')
                        ->nullable(),

                    TextInput::make('label')
                        ->label('Batch Label / Notes')
                        ->placeholder('e.g. Counter Stock, Event Promo')
                        ->maxLength(100),

                    Select::make('payment_method')
                        ->label('Funding Source')
                        ->options(function () {
                            $options = [
                                'wallet' => 'Prepaid Wallet Deduction (Automated Ledger)',
                            ];
                            if (auth()->user()?->hasRole('super_admin') || auth()->user()?->email === 'amazino33@gmail.com') {
                                $options['admin_override'] = 'Super Admin Audit Override (Zero-Cost / Testing)';
                            }
                            return $options;
                        })
                        ->default('wallet')
                        ->required()
                        ->live(),

                    Textarea::make('override_reason')
                        ->label('Audit Reason for Zero-Cost Creation')
                        ->placeholder('Explain why this batch is complimentary/testing (logged to SystemLog)')
                        ->required(fn ($get) => $get('payment_method') === 'admin_override')
                        ->visible(fn ($get) => $get('payment_method') === 'admin_override'),

                    Placeholder::make('financial_summary')
                        ->label('Financial Summary')
                        ->content(function ($get) {
                            $planId = $get('plan_id');
                            $qty = (int) ($get('quantity') ?: 0);
                            $plan = $planId ? Plan::find($planId) : null;
                            $unitPrice = (float) ($plan?->price ?? 0);
                            $total = $unitPrice * $qty;
                            $walletBal = (float) (auth()->user()?->wallet_balance ?? 0);
                            $method = $get('payment_method');

                            if ($method === 'admin_override') {
                                return new \Illuminate\Support\HtmlString(
                                    "<div class='p-3 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-lg text-sm text-amber-800 dark:text-amber-200'>"
                                    . "<strong>Zero-Cost Audit Override Active:</strong> This batch will be recorded as non-monetary and flagged in the system audit logs."
                                    . "</div>"
                                );
                            }

                            $canAfford = $walletBal >= $total;
                            $colorClass = $canAfford ? 'text-green-700 dark:text-green-300 bg-green-50 dark:bg-green-900/20 border-green-200' : 'text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 border-red-200';

                            return new \Illuminate\Support\HtmlString(
                                "<div class='p-3 rounded-lg border text-sm space-y-1 {$colorClass}'>"
                                . "<div><strong>Total Batch Cost:</strong> ₦" . number_format($total, 2) . " ({$qty} vouchers × ₦" . number_format($unitPrice, 2) . ")</div>"
                                . "<div><strong>Your Wallet Balance:</strong> ₦" . number_format($walletBal, 2) . "</div>"
                                . (! $canAfford ? "<div class='font-bold mt-1'>⚠️ Insufficient wallet balance! Please fund your wallet before creating this batch.</div>" : "<div class='font-semibold mt-1'>✅ Wallet balance is sufficient. Amount will be deducted automatically.</div>")
                                . "</div>"
                            );
                        }),
                ])
                ->action(function (array $data): void {
                    $plan = Plan::findOrFail($data['plan_id']);
                    $qty = (int) $data['quantity'];
                    $user = auth()->user();

                    try {
                        $service = app(VoucherGenerationService::class);
                        $batch = $service->generateBatch($user, $plan, $qty, [
                            'router_id'       => $data['router_id'] ?? null,
                            'payment_method'  => $data['payment_method'] ?? 'wallet',
                            'override_reason' => $data['override_reason'] ?? null,
                            'label'           => $data['label'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Batch Generated Successfully!')
                            ->body("Created {$qty} vouchers in batch {$batch->batch_code}. Total: ₦" . number_format((float) $batch->total_cost, 2) . ".")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Generation Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}