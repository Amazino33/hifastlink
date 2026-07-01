<?php

// app/Http/Controllers/VoucherController.php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    /**
     * Voucher management page — accessible to family heads and app admins.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->is_family_admin && ! $user->isAdmin()) {
            abort(403, 'Only family heads or admins can manage vouchers.');
        }

        $vouchers = \App\Models\Voucher::where('created_by', $user->id)
            ->with('plan')
            ->latest()
            ->paginate(15);

        $plans = \App\Models\Plan::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'validity_days', 'data_limit', 'limit_unit', 'price']);

        $isAdmin       = $user->isAdmin();
        $isFamilyAdmin = (bool) $user->is_family_admin;

        // Compute plan limits for non-admin family heads so the view can enforce caps
        $planLimits = null;
        if (! $isAdmin && $isFamilyAdmin && $user->plan) {
            $plan            = $user->plan;
            $planIsUnlimited = $plan->limit_unit === 'Unlimited';
            $planDataMb      = null;
            if (! $planIsUnlimited && $plan->data_limit) {
                $planDataMb = $plan->limit_unit === 'GB'
                    ? (int) ($plan->data_limit * 1024)
                    : (int) $plan->data_limit;
            }
            $planLimits = [
                'plan_name'            => $plan->name,
                'validity_days'        => (int) $plan->validity_days,
                'is_unlimited'         => $planIsUnlimited,
                'data_limit_mb'        => $planDataMb,
                'data_human'           => $planIsUnlimited ? 'Unlimited' : ($plan->data_limit . ' ' . $plan->limit_unit),
                'speed_limit_download' => (int) ($plan->speed_limit_download ?? 0),
                'speed_limit_upload'   => (int) ($plan->speed_limit_upload ?? 0),
            ];
        }

        return view('vouchers.index', compact('vouchers', 'plans', 'isAdmin', 'isFamilyAdmin', 'planLimits'));
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
     * Generate vouchers — supports "quick" (inherits plan) and "custom" modes.
     */
    public function generate(Request $request)
    {
        $user = $request->user();

        if (! $user->is_family_admin && ! $user->isAdmin()) {
            return back()->with('error', 'You do not have permission to create vouchers.');
        }

        $mode = $request->input('mode', 'quick');

        if ($mode === 'custom') {
            return $this->generateCustom($request, $user);
        }

        return $this->generateQuick($request, $user);
    }

    private function generateQuick(Request $request, $user)
    {
        $maxAllowed       = $user->plan->family_limit ?? $user->family_limit ?? 10;
        $activeCount      = Voucher::where('created_by', $user->id)
            ->where(function ($q) {
                $q->whereColumn('used_count', '<', 'max_uses')
                  ->where(fn ($q2) => $q2->whereNull('expires_at')->orWhere('expires_at', '>', now()));
            })
            ->count();
        $remainingSlots   = $maxAllowed - 1 - $activeCount;
        $quantity         = (int) $request->input('quantity', 1);

        if ($remainingSlots <= 0) {
            return back()->with('error', 'Slot limit reached. Remove old vouchers to add more.');
        }
        if ($quantity > $remainingSlots) {
            return back()->with('error', "Only {$remainingSlots} slot(s) remaining.");
        }

        $duration    = ($user->plan->validity_days ?? 1) * 24;
        $rawLimit    = $user->plan->data_limit ?? 0;
        $dataLimitMb = $rawLimit > 1000000 ? (int) ($rawLimit / 1048576) : (int) $rawLimit;

        for ($i = 0; $i < $quantity; $i++) {
            Voucher::create([
                'code'          => Voucher::generateCode(),
                'plan_id'       => $user->plan_id,
                'created_by'    => $user->id,
                'router_id'     => $user->router_id,
                'duration_hours'=> $duration,
                'data_limit_mb' => $dataLimitMb ?: null,
                'max_uses'      => 1,
                'is_used'       => false,
            ]);
        }

        return back()->with('success', "{$quantity} voucher(s) created. {$remainingSlots} slot(s) remaining.");
    }

    private function generateCustom(Request $request, $user)
    {
        $request->validate([
            'quantity'             => 'required|integer|min:1|max:100',
            'validity_days'        => 'required|integer|min:1|max:365',
            'max_uses'             => 'required|integer|min:1|max:500',
            'data_limit_mb'        => 'nullable|integer|min:1',
            'speed_limit_upload'   => 'nullable|integer|min:0',
            'speed_limit_download' => 'nullable|integer|min:0',
            'plan_id'              => 'nullable|exists:plans,id',
            'label'                => 'nullable|string|max:100',
        ]);

        $isUnlimited  = $request->boolean('is_unlimited');
        $validityDays = (int) $request->input('validity_days');
        $quantity     = (int) $request->input('quantity');

        // Convert data limit to MB if user selected GB
        $rawDataLimit = $request->input('data_limit_mb');
        if ($rawDataLimit && $request->input('data_unit') === 'GB') {
            $rawDataLimit = (int) ($rawDataLimit * 1024);
        }

        // Non-admins: enforce plan caps on specs and slot count
        if (! $user->isAdmin()) {
            $plan = $user->plan;

            if (! $plan) {
                return back()->with('error', 'You need an active plan to create custom vouchers.');
            }

            // Validity
            if ($validityDays > (int) $plan->validity_days) {
                return back()->with('error', "Validity cannot exceed your plan limit of {$plan->validity_days} days.");
            }

            // Unlimited data
            if ($isUnlimited && $plan->limit_unit !== 'Unlimited') {
                return back()->with('error', 'You cannot create unlimited vouchers — your plan has a data cap.');
            }

            // Data allowance
            if (! $isUnlimited && $plan->limit_unit !== 'Unlimited' && $rawDataLimit) {
                $planDataMb = $plan->limit_unit === 'GB'
                    ? (int) ($plan->data_limit * 1024)
                    : (int) $plan->data_limit;
                if ((int) $rawDataLimit > $planDataMb) {
                    $humanLimit = $plan->data_limit . ' ' . $plan->limit_unit;
                    return back()->with('error', "Data allowance cannot exceed your plan limit ({$humanLimit}).");
                }
            }

            // Speed limits
            if ($plan->speed_limit_download && (int) $request->input('speed_limit_download') > $plan->speed_limit_download) {
                return back()->with('error', "Download speed cannot exceed your plan limit of {$plan->speed_limit_download} Kbps.");
            }
            if ($plan->speed_limit_upload && (int) $request->input('speed_limit_upload') > $plan->speed_limit_upload) {
                return back()->with('error', "Upload speed cannot exceed your plan limit of {$plan->speed_limit_upload} Kbps.");
            }

            // Slot count
            $maxAllowed     = $plan->family_limit ?? $user->family_limit ?? 10;
            $activeCount    = Voucher::where('created_by', $user->id)->count();
            $remainingSlots = $maxAllowed - 1 - $activeCount;
            if ($quantity > $remainingSlots) {
                return back()->with('error', "Only {$remainingSlots} slot(s) remaining in your plan.");
            }
        }

        for ($i = 0; $i < $quantity; $i++) {
            Voucher::create([
                'code'                 => Voucher::generateCode(),
                'plan_id'              => $request->input('plan_id') ?: null,
                'created_by'           => $user->id,
                'router_id'            => $user->router_id,
                'duration_hours'       => $validityDays * 24,
                'data_limit_mb'        => $isUnlimited ? null : ($rawDataLimit ?: null),
                'is_unlimited'         => $isUnlimited,
                'speed_limit_upload'   => $request->input('speed_limit_upload') ?: null,
                'speed_limit_download' => $request->input('speed_limit_download') ?: null,
                'max_uses'             => (int) $request->input('max_uses', 1),
                'label'                => $request->input('label') ?: null,
                'is_used'              => false,
            ]);
        }

        return back()->with('success', "{$quantity} custom voucher(s) created.");
    }

    /**
     * Success page shown after a voucher connects the user to the router.
     * The code arrives as a session flash (direct browser) or query param
     * (captive portal — session may not survive the MikroTik redirect hop).
     */
    public function success(\Illuminate\Http\Request $request)
    {
        $code = session('voucher_code') ?? $request->query('code');
        return view('vouchers.success', compact('code'));
    }

    public function bulkDestroy(\Illuminate\Http\Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'No vouchers selected.');
        }

        $vouchers = Voucher::whereIn('id', $ids)
            ->where('created_by', auth()->id())
            ->get();

        foreach ($vouchers as $voucher) {
            \App\Models\RadCheck::where('username', $voucher->code)->delete();
            \App\Models\RadReply::where('username', $voucher->code)->delete();
            $voucher->delete();
        }

        return back()->with('success', $vouchers->count() . ' voucher(s) revoked successfully.');
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
