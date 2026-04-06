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

        // 1. Identify the Limit (Priority: Plan > User Column > Default 10)
        $maxAllowed = $user->plan->max_devices
                      ?? $user->family_limit
                      ?? 10;

        // 2. Count ACTIVE vouchers (not expired, not fully used)
        $activeVoucherCount = Voucher::where('created_by', $user->id)->count();

        // 3. Calculate remaining guest slots (Total - 1 for Head - Active Vouchers)
        $remainingSlots = $maxAllowed - 1 - $activeVoucherCount;

        // 4. Validate the request
        $quantityRequested = (int) $request->input('quantity', 1);

        if ($remainingSlots <= 0) {
            return back()->with('error', 'Limit reached! Your plan allows '.($maxAllowed - 1).' guests. Remove old vouchers to add more.');
        }

        if ($quantityRequested > $remainingSlots) {
            return back()->with('error', "You only have {$remainingSlots} guest slots left.");
        }

        // 5. Inherit Plan Details
        $duration = $user->plan->duration_hours ?? 24;

        // Convert data_limit to MB if it's currently in bytes (common for RADIUS)
        $rawLimit = $user->plan->data_limit ?? 0;
        $dataLimitMb = $rawLimit > 1000000 ? (int) ($rawLimit / 1048576) : $rawLimit;

        // 6. Create the Vouchers
        for ($i = 0; $i < $quantityRequested; $i++) {
            \App\Models\Voucher::create([
                'code' => \App\Models\Voucher::generateCode(),
                'plan_id' => $user->plan_id,
                'created_by' => $user->id,
                'router_id' => $user->router_id,
                'duration_hours' => $duration,
                'data_limit_mb' => $dataLimitMb,
                'max_uses' => 1, // 1 device per guest voucher
                'expires_at' => now()->addHours($duration),
                'is_used' => false,
            ]);
        }

        return back()->with('success', "{$quantityRequested} voucher(s) created. You have ".($remainingSlots - $quantityRequested).' slots remaining.');
    }

    /**
     * Simple success page shown when a voucher is used
     * outside the captive portal context.
     */
    public function success()
    {
        return view('vouchers.success');
    }

    public function destroy(Voucher $voucher)
    {
        abort_unless(auth()->id() === $voucher->created_by, 403);

        // Clean up RADIUS so the code stops working immediately
        \App\Models\RadCheck::where('username', $voucher->code)->delete();
        \App\Models\RadReply::where('username', $voucher->code)->delete();

        $voucher->delete();

        return back()->with('success', 'Voucher removed and slot freed.');
    }
}
