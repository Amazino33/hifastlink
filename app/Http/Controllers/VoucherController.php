<?php

// app/Http/Controllers/VoucherController.php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    /**
     * Show the family head's voucher management page.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Safety check: Only family admins should see this
        if (! $user->is_family_admin) {
            abort(403, 'Only family heads can manage vouchers.');
        }

        // Get vouchers created by this user, ordered by newest first
        $vouchers = \App\Models\Voucher::where('created_by', $user->id)
            ->latest()
            ->paginate(10);

        return view('vouchers.index', compact('vouchers'));
    }

    /**
     * Lightweight endpoint — frontend calls this on input blur
     * to decide whether to show or hide the password field.
     */
    public function checkInput(Request $request): JsonResponse
    {
        $input = $request->input('input', '');

        return response()->json([
            'type' => Voucher::isVoucherCode($input) ? 'voucher' : 'account',
        ]);
    }

    /**
     * Family head voucher generation.
     */
    public function generate(Request $request)
{
    $user = $request->user();
    $plan = $user->plan;

    if (!$plan) {
        return back()->with('error', 'You need an active plan to generate vouchers.');
    }

    // 1. Get the limit from the plan (e.g., 5 devices)
    $maxAllowed = $plan->max_devices ?? 1;

    // 2. Count current ACTIVE vouchers created by this user
    // We only count vouchers that aren't expired and aren't fully used yet
    $activeVoucherCount = Voucher::where('created_by', $user->id)
        ->where('is_used', false)
        ->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })
        ->count();

    // 3. Calculate remaining slots
    // We subtract 1 because the Family Head counts as 1 device themselves
    $remainingSlots = $maxAllowed - 1 - $activeVoucherCount;

    $quantityRequested = (int) $request->input('quantity');

    // 4. Validation: Check if they are overstepping their plan
    if ($remainingSlots <= 0) {
        return back()->with('error', "Limit reached! Your plan only allows {$maxAllowed} total devices. Delete old vouchers to make space.");
    }

    if ($quantityRequested > $remainingSlots) {
        return back()->with('error', "You can only create {$remainingSlots} more voucher(s) based on your plan limits.");
    }

    // 5. Proceed to generate if they pass the check
    for ($i = 0; $i < $quantityRequested; $i++) {
        Voucher::create([
            'code'           => Voucher::generateCode(),
            'plan_id'        => $plan->id,
            'created_by'     => $user->id,
            'router_id'      => $user->router_id,
            'duration_hours' => $plan->duration_hours ?? 24,
            'data_limit_mb'  => $plan->data_limit_mb, // Inherit from plan
            'max_uses'       => 1, // Usually 1 device per voucher for guests
            'expires_at'     => now()->addHours($plan->duration_hours ?? 24),
        ]);
    }

    return back()->with('success', "{$quantityRequested} voucher(s) generated successfully.");
}

    /**
     * Simple success page shown when a voucher is used
     * outside the captive portal context.
     */
    public function success()
    {
        return view('vouchers.success');
    }
}
