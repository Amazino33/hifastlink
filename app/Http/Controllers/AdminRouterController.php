<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Router;
use App\Models\RadAcct;

class AdminRouterController extends Controller
{
    public function index(Request $request)
    {
        $txs = Transaction::whereNull('router_id')
            ->latest()
            ->paginate(20, ['*'], 'txpage');

        $payments = Payment::whereNull('router_id')
            ->latest()
            ->paginate(20, ['*'], 'paypage');

        // Preload nearby sessions for displayed rows (±7 days)
        $days = 7;
        $txSessions = [];
        foreach ($txs as $tx) {
            $txSessions[$tx->id] = RadAcct::whereRaw('LOWER(username) = ?', [mb_strtolower(optional($tx->user)->username)])
                ->whereBetween('acctstarttime', [$tx->created_at->copy()->subDays($days), $tx->created_at->copy()->addDays($days)])
                ->orderByDesc('acctstarttime')
                ->limit(5)
                ->get();
        }

        $paySessions = [];
        foreach ($payments as $pay) {
            $paySessions[$pay->id] = RadAcct::whereRaw('LOWER(username) = ?', [mb_strtolower(optional($pay->user)->username)])
                ->whereBetween('acctstarttime', [$pay->created_at->copy()->subDays($days), $pay->created_at->copy()->addDays($days)])
                ->orderByDesc('acctstarttime')
                ->limit(5)
                ->get();
        }

        $routers = Router::where('is_active', true)->orderBy('name')->get();

        return view('admin.unmatched-router-refs', compact('txs', 'payments', 'txSessions', 'paySessions', 'routers'));
    }

    public function assign(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:transaction,payment',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'router_id' => 'nullable|integer|exists:routers,id',
        ]);

        $model = $data['type'] === 'transaction' ? Transaction::class : Payment::class;

        $updated = $model::whereIn('id', $data['ids'])->update(['router_id' => $data['router_id']]);

        return redirect()->back()->with('success', "Updated {$updated} records.");
    }
}
