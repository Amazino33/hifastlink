<?php

// app/Http/Controllers/VoucherController.php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    /**
     * Voucher management page — accessible to router owners and app admins.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $isRouterOwner = \App\Models\Router::where('owner_id', $user->id)->exists();

        if (! $user->isAdmin() && ! $isRouterOwner) {
            abort(403, 'Only router owners or admins can manage vouchers.');
        }

        $vouchers = \App\Models\Voucher::where('created_by', $user->id)
            ->with(['plan', 'batch'])
            ->latest()
            ->paginate(15);

        $batches = \App\Models\VoucherBatch::where('user_id', $user->id)
            ->with(['plan', 'router'])
            ->latest()
            ->take(10)
            ->get();

        $plans = \App\Models\Plan::where('is_active', true)
            ->orderBy('price')
            ->get(['id', 'name', 'validity_days', 'data_limit', 'limit_unit', 'price']);

        $isAdmin = $user->isAdmin();
        $walletBalance = (float) ($user->wallet_balance ?? 0);
        $ownedRouters = \App\Models\Router::where('owner_id', $user->id)->get();

        return view('vouchers.index', compact('vouchers', 'batches', 'plans', 'isAdmin', 'isRouterOwner', 'walletBalance', 'ownedRouters'));
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
     * Generate vouchers — prepaid via wallet balance deduction or family slot allocation.
     */
    public function generate(Request $request)
    {
        $user = $request->user();

        $isRouterOwner = \App\Models\Router::where('owner_id', $user->id)->exists();

        if (! $user->isAdmin() && ! $isRouterOwner && ! $user->is_family_admin) {
            return back()->with('error', 'You do not have permission to create vouchers.');
        }

        $request->validate([
            'plan_id'   => 'required|exists:plans,id',
            'quantity'  => 'required|integer|min:1|max:100',
            'router_id' => 'nullable|exists:routers,id',
            'label'     => 'nullable|string|max:100',
        ]);

        $plan = \App\Models\Plan::findOrFail($request->input('plan_id'));
        $quantity = (int) $request->input('quantity', 1);
        $routerId = $request->input('router_id') ?: ($user->router_id ?: \App\Models\Router::where('owner_id', $user->id)->value('id'));

        try {
            $service = app(\App\Services\VoucherGenerationService::class);
            $batch = $service->generateBatch($user, $plan, $quantity, [
                'router_id'      => $routerId,
                'payment_method' => 'wallet',
                'label'          => $request->input('label'),
            ]);

            return back()->with('success', "Batch {$batch->batch_code} generated successfully! {$quantity} vouchers created. ₦" . number_format((float) $batch->total_cost, 2) . " deducted from your wallet.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
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
