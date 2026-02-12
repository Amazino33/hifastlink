<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    // Middleware is applied in routes/web.php

    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('connection_status', 'active')->count(),
            'suspended_users' => User::where('connection_status', 'suspended')->count(),
            'total_revenue' => User::sum('wallet_balance'), // This is just wallet balance, you'll need proper revenue tracking
            'online_users' => User::where('connection_status', 'active')
                                 ->where('last_online', '>', now()->subMinutes(5))
                                 ->count(),
        ];

        $recent_sessions = \DB::table('user_sessions')
                             ->join('users', 'user_sessions.user_id', '=', 'users.id')
                             ->select('user_sessions.*', 'users.name', 'users.username')
                             ->orderBy('user_sessions.created_at', 'desc')
                             ->limit(10)
                             ->get();

        return view('admin.dashboard', compact('stats', 'recent_sessions'));
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