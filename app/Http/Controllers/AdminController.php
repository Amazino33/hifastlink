<?php

namespace App\Http\Controllers;

use App\Models\RadAcct;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;

class AdminController extends Controller
{
    // Middleware is applied in routes/web.php

    public function dashboard()
    {
        $dataBytes = (int) RadAcct::where(fn($q) => $q->whereNull('acctstoptime')->orWhereDate('acctstoptime', today()))
            ->sum(DB::raw('COALESCE(acctinputoctets,0) + COALESCE(acctoutputoctets,0)'));

        $stats = [
            'online_users'       => RadAcct::whereNull('acctstoptime')->distinct('username')->count('username'),
            'today_revenue'      => (float) Transaction::whereIn('status', ['completed', 'success'])->whereDate('created_at', today())->sum('amount'),
            'active_subscribers' => User::whereNotNull('plan_id')->whereNotNull('plan_expiry')->where('plan_expiry', '>', now())->count(),
            'data_consumed'      => Number::fileSize($dataBytes),
        ];

        $recent_sessions = DB::table('radacct')
            ->leftJoin('users', DB::raw('LOWER(radacct.username)'), '=', DB::raw('LOWER(users.username)'))
            ->select('radacct.username', 'radacct.acctstarttime as created_at', 'users.name')
            ->orderBy('radacct.acctstarttime', 'desc')
            ->limit(10)
            ->get();

        $allRouters = \App\Models\Router::where('is_active', true)->orderBy('name')->get();

        return view('admin.dashboard', compact('stats', 'recent_sessions', 'allRouters'));
    }

    public function users()
    {
        $users = User::with('sessions')->paginate(20);
        return view('admin.users', compact('users'));
    }

    public function userDetails(User $user)
    {
        $sessions = $user->sessions()->orderBy('created_at', 'desc')->paginate(10);
        return view('admin.user-details', compact('user', 'sessions'));
    }

    public function suspendUser(User $user)
    {
        $user->update(['connection_status' => 'suspended']);
        \Artisan::call('radius:sync-users');

        Log::info("Admin suspended user: {$user->username}");
        return back()->with('success', 'User suspended successfully');
    }

    public function activateUser(User $user)
    {
        $user->update(['connection_status' => 'active']);
        \Artisan::call('radius:sync-users');

        Log::info("Admin activated user: {$user->username}");
        return back()->with('success', 'User activated successfully');
    }

    public function resetData(User $user)
    {
        $user->update(['data_used' => 0]);
        Log::info("Admin reset data for user: {$user->username}");
        return back()->with('success', 'Data usage reset successfully');
    }
}