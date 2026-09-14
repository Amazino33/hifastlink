<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\SystemLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoucherGenerationService
{
    /**
     * Generate a batch of vouchers with strict financial tracking.
     *
     * @param  User  $user  The user/admin creating the batch
     * @param  Plan  $plan  The linked plan
     * @param  int   $quantity  Number of vouchers to generate
     * @param  array $options  Optional settings: router_id, payment_method, override_reason, notes, label, max_uses
     * @return VoucherBatch
     *
     * @throws \RuntimeException
     */
    public function generateBatch(User $user, Plan $plan, int $quantity, array $options = []): VoucherBatch
    {
        if ($quantity < 1 || $quantity > 500) {
            throw new \InvalidArgumentException('Quantity must be between 1 and 500.');
        }

        $routerId = $options['router_id'] ?? $user->router_id;
        $paymentMethod = $options['payment_method'] ?? 'wallet';
        $unitPrice = (float) ($plan->price ?? 0);
        $totalCost = $unitPrice * $quantity;
        $maxUses = (int) ($options['max_uses'] ?? 1);
        $label = $options['label'] ?? ($options['notes'] ?? null);

        // Derive plan specifications
        $durationHours = (int) (($plan->validity_days ?? 1) * 24);
        $isUnlimited = ($plan->limit_unit === 'Unlimited') || (bool) ($plan->is_unlimited ?? false);
        $dataLimitMb = null;

        if (! $isUnlimited && $plan->data_limit) {
            $dataLimitMb = $plan->limit_unit === 'GB'
                ? (int) ($plan->data_limit * 1024)
                : (int) $plan->data_limit;
        }

        $speedDownload = $plan->speed_limit_download ? (int) $plan->speed_limit_download : null;
        $speedUpload = $plan->speed_limit_upload ? (int) $plan->speed_limit_upload : null;

        // ── 1. Admin Override (Super Admin Emergency / Testing) ───────
        if ($paymentMethod === 'admin_override') {
            if (! $user->isAdmin()) {
                throw new \RuntimeException('Unauthorized: Only administrators can use admin override.');
            }

            $reason = trim($options['override_reason'] ?? '');
            if (empty($reason)) {
                throw new \InvalidArgumentException('A mandatory audit reason is required for administrator override.');
            }

            return DB::transaction(function () use (
                $user, $plan, $quantity, $routerId, $reason, $durationHours,
                $isUnlimited, $dataLimitMb, $speedDownload, $speedUpload, $maxUses, $label
            ) {
                $batchCode = VoucherBatch::generateBatchCode();

                $batch = VoucherBatch::create([
                    'batch_code'     => $batchCode,
                    'user_id'        => $user->id,
                    'plan_id'        => $plan->id,
                    'router_id'      => $routerId,
                    'quantity'       => $quantity,
                    'unit_price'     => 0.00,
                    'total_cost'     => 0.00,
                    'payment_method' => 'admin_override',
                    'transaction_id' => null,
                    'status'         => 'completed',
                    'notes'          => 'Admin Override Reason: ' . $reason,
                ]);

                for ($i = 0; $i < $quantity; $i++) {
                    Voucher::create([
                        'batch_id'             => $batch->id,
                        'code'                 => Voucher::generateCode(),
                        'plan_id'              => $plan->id,
                        'price'                => 0.00,
                        'transaction_id'       => null,
                        'created_by'           => $user->id,
                        'router_id'            => $routerId,
                        'duration_hours'       => $durationHours,
                        'data_limit_mb'        => $dataLimitMb,
                        'is_unlimited'         => $isUnlimited,
                        'speed_limit_upload'   => $speedUpload,
                        'speed_limit_download' => $speedDownload,
                        'max_uses'             => $maxUses,
                        'used_count'           => 0,
                        'label'                => $label,
                        'is_used'              => false,
                    ]);
                }

                // Log to SystemLog
                try {
                    SystemLog::create([
                        'level'   => 'warning',
                        'event'   => 'voucher_batch_admin_override',
                        'message' => "Super Admin {$user->name} ({$user->email}) created zero-cost voucher batch {$batchCode} ({$quantity} vouchers) for plan '{$plan->name}'. Reason: {$reason}",
                        'user_id' => $user->id,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to log admin override to SystemLog: ' . $e->getMessage());
                }

                return $batch;
            });
        }

        // ── 2. Wallet Payment (Strict Checkmate) ──────────────────────
        if ($paymentMethod === 'wallet') {
            if (! $user->hasSufficientWalletBalance($totalCost)) {
                $currentBal = number_format((float) ($user->wallet_balance ?? 0), 2);
                $needed = number_format($totalCost, 2);
                throw new \RuntimeException("Insufficient wallet balance. You need ₦{$needed}, but your balance is ₦{$currentBal}. Please top up your wallet.");
            }

            return DB::transaction(function () use (
                $user, $plan, $quantity, $totalCost, $unitPrice, $routerId,
                $durationHours, $isUnlimited, $dataLimitMb, $speedDownload,
                $speedUpload, $maxUses, $label, $options
            ) {
                // Deduct from wallet and record completed transaction
                $desc = "Purchased {$quantity}x '{$plan->name}' vouchers";
                $transaction = $user->deductWallet($totalCost, $desc, $plan->id, $routerId);

                $batchCode = VoucherBatch::generateBatchCode();

                $batch = VoucherBatch::create([
                    'batch_code'     => $batchCode,
                    'user_id'        => $user->id,
                    'plan_id'        => $plan->id,
                    'router_id'      => $routerId,
                    'quantity'       => $quantity,
                    'unit_price'     => $unitPrice,
                    'total_cost'     => $totalCost,
                    'payment_method' => 'wallet',
                    'transaction_id' => $transaction->id,
                    'status'         => 'completed',
                    'notes'          => $options['notes'] ?? null,
                ]);

                for ($i = 0; $i < $quantity; $i++) {
                    Voucher::create([
                        'batch_id'             => $batch->id,
                        'code'                 => Voucher::generateCode(),
                        'plan_id'              => $plan->id,
                        'price'                => $unitPrice,
                        'transaction_id'       => $transaction->id,
                        'created_by'           => $user->id,
                        'router_id'            => $routerId,
                        'duration_hours'       => $durationHours,
                        'data_limit_mb'        => $dataLimitMb,
                        'is_unlimited'         => $isUnlimited,
                        'speed_limit_upload'   => $speedUpload,
                        'speed_limit_download' => $speedDownload,
                        'max_uses'             => $maxUses,
                        'used_count'           => 0,
                        'label'                => $label,
                        'is_used'              => false,
                    ]);
                }

                return $batch;
            });
        }

        // ── 3. Paystack Direct (Callback completed batch) ─────────────
        if ($paymentMethod === 'paystack') {
            $transactionId = $options['transaction_id'] ?? null;

            return DB::transaction(function () use (
                $user, $plan, $quantity, $totalCost, $unitPrice, $routerId,
                $transactionId, $durationHours, $isUnlimited, $dataLimitMb,
                $speedDownload, $speedUpload, $maxUses, $label, $options
            ) {
                $batchCode = VoucherBatch::generateBatchCode();

                $batch = VoucherBatch::create([
                    'batch_code'     => $batchCode,
                    'user_id'        => $user->id,
                    'plan_id'        => $plan->id,
                    'router_id'      => $routerId,
                    'quantity'       => $quantity,
                    'unit_price'     => $unitPrice,
                    'total_cost'     => $totalCost,
                    'payment_method' => 'paystack',
                    'transaction_id' => $transactionId,
                    'status'         => 'completed',
                    'notes'          => $options['notes'] ?? null,
                ]);

                for ($i = 0; $i < $quantity; $i++) {
                    Voucher::create([
                        'batch_id'             => $batch->id,
                        'code'                 => Voucher::generateCode(),
                        'plan_id'              => $plan->id,
                        'price'                => $unitPrice,
                        'transaction_id'       => $transactionId,
                        'created_by'           => $user->id,
                        'router_id'            => $routerId,
                        'duration_hours'       => $durationHours,
                        'data_limit_mb'        => $dataLimitMb,
                        'is_unlimited'         => $isUnlimited,
                        'speed_limit_upload'   => $speedUpload,
                        'speed_limit_download' => $speedDownload,
                        'max_uses'             => $maxUses,
                        'used_count'           => 0,
                        'label'                => $label,
                        'is_used'              => false,
                    ]);
                }

                return $batch;
            });
        }

        throw new \InvalidArgumentException("Invalid payment method '{$paymentMethod}'.");
    }
}
