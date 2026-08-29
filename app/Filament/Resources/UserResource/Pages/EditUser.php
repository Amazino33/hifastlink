<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PlanSyncService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?int $originalPlanId = null;

    protected function beforeSave(): void
    {
        // Capture the plan before Filament overwrites it
        $this->originalPlanId = (int) $this->record->getOriginal('plan_id');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $formState = $this->form->getState();

        // Sync roles
        if (array_key_exists('roles', $formState)) {
            $record->syncRoles($formState['roles'] ?? []);
        }

        // When admin assigns a NEW plan, calculate and apply data_limit properly.
        // The form only sets plan_id + plan_expiry — we need to also set data_limit
        // so RADIUS knows the quota. Without this, the user has a plan but 0 bytes.
        $newPlanId = (int) $record->plan_id;
        if ($newPlanId && $newPlanId !== $this->originalPlanId) {
            $plan = Plan::find($newPlanId);
            if ($plan) {
                $planBytes = $this->planToBytes($plan);

                $record->data_limit      = $planBytes;
                $record->data_used       = 0;
                $record->plan_started_at = $record->plan_started_at ?? now();
                $record->connection_status = 'active';
                $record->saveQuietly(); // skip observer re-fire

                // Manually trigger RADIUS sync with fresh data
                try {
                    PlanSyncService::syncUserPlan($record->fresh());
                } catch (\Throwable $e) {
                    Log::error('EditUser afterSave: RADIUS sync failed — ' . $e->getMessage());
                    Notification::make()
                        ->title('Plan saved — RADIUS sync failed')
                        ->body('Plan was assigned but RADIUS could not be updated: ' . $e->getMessage())
                        ->warning()
                        ->send();
                    return;
                }

                Notification::make()
                    ->title('Plan activated')
                    ->body("{$plan->name} assigned. Data limit: " . ($planBytes ? Number::fileSize($planBytes) : 'Unlimited') . '.')
                    ->success()
                    ->send();
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // ── Verify a Paystack reference and activate the plan ──────────
            Action::make('verify_payment')
                ->label('Activate by Receipt')
                ->color('success')
                ->icon('heroicon-o-credit-card')
                ->modalHeading('Verify Paystack Payment')
                ->modalDescription('Enter the Paystack payment reference from the customer\'s receipt or email. The system will verify it with Paystack and activate the plan.')
                ->modalSubmitActionLabel('Verify & Activate')
                ->form([
                    TextInput::make('reference')
                        ->label('Paystack Reference')
                        ->required()
                        ->placeholder('PaystackRef_xxxxxxxxxxxx')
                        ->helperText('Found on the payment receipt or in Paystack dashboard → Transactions'),
                ])
                ->action(function (array $data) {
                    $this->verifyAndActivatePayment($data['reference']);
                }),
        ];
    }

    private function verifyAndActivatePayment(string $reference): void
    {
        $reference = trim($reference);

        // Already processed?
        if (Transaction::where('reference', $reference)->exists()) {
            Notification::make()
                ->title('Already activated')
                ->body("Reference {$reference} was already processed.")
                ->info()
                ->send();
            return;
        }

        // Verify with Paystack
        try {
            $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
                ->timeout(15)
                ->get(rtrim(env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'), '/') . '/transaction/verify/' . urlencode($reference));
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Paystack unreachable')
                ->body($e->getMessage())
                ->danger()
                ->send();
            return;
        }

        if (! $response->successful()) {
            Notification::make()
                ->title('Paystack API error')
                ->body('HTTP ' . $response->status() . ': ' . ($response->json('message') ?? 'Unknown error'))
                ->danger()
                ->send();
            return;
        }

        $body = $response->json();
        $txData = $body['data'] ?? [];

        if (($txData['status'] ?? '') !== 'success') {
            Notification::make()
                ->title('Payment not successful')
                ->body('Paystack status: ' . ($txData['status'] ?? 'unknown') . '. Only completed payments can be activated.')
                ->warning()
                ->send();
            return;
        }

        // Resolve plan and user from metadata
        $metadata = $txData['metadata'] ?? [];
        $planId   = $metadata['plan_id']  ?? null;
        $userId   = $metadata['user_id']  ?? null;

        // If metadata is missing, fall back to the user being edited
        $user = $userId ? User::find($userId) : null;
        $user ??= $this->record;

        $plan = $planId ? Plan::find($planId) : null;

        if (! $plan) {
            Notification::make()
                ->title('Plan not found')
                ->body("Metadata plan_id={$planId} not found. Select the correct plan for this user and save first, then use this button to mark the payment.")
                ->warning()
                ->send();
            return;
        }

        // Activate plan on user
        $planBytes = $this->planToBytes($plan);

        $user->plan_id         = $plan->id;
        $user->data_limit      = $planBytes;
        $user->data_used       = 0;
        $user->plan_expiry     = now()->addDays($plan->validity_days ?? 0);
        $user->plan_started_at = now();
        $user->connection_status = 'active';
        if ($plan->is_family) {
            $user->is_family_admin = true;
        }
        $user->family_limit = $plan->family_limit ?? 0;
        $user->save(); // triggers UserPlanObserver → PlanSyncService

        // Record the transaction so this reference can't double-activate
        Transaction::firstOrCreate(
            ['reference' => $reference],
            [
                'user_id'   => $user->id,
                'plan_id'   => $plan->id,
                'amount'    => ($txData['amount'] ?? 0) / 100,
                'status'    => 'completed',
                'gateway'   => 'paystack',
                'paid_at'   => now(),
                'router_id' => $user->router_id,
            ]
        );

        Log::info("Admin manually activated plan via Paystack reference {$reference} for user {$user->username}");

        Notification::make()
            ->title('Plan activated')
            ->body("{$plan->name} activated for {$user->display_name}. " . ($planBytes ? Number::fileSize($planBytes) . ' data.' : 'Unlimited data.'))
            ->success()
            ->persistent()
            ->send();

        // Refresh the page so the form shows updated state
        $this->redirect($this->getResource()::getUrl('edit', ['record' => $user]));
    }

    private function planToBytes(Plan $plan): ?int
    {
        if ($plan->limit_unit === 'Unlimited' || ! $plan->data_limit) {
            return null;
        }
        return $plan->limit_unit === 'GB'
            ? (int) ($plan->data_limit * 1073741824)
            : (int) ($plan->data_limit * 1048576);
    }
}
