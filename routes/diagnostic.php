<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\RadCheck;
use App\Models\RadUserGroup;
use App\Models\RadGroupReply;
use App\Models\RadAcct;

Route::get('/admin/radius-diagnostic/{username}', function ($username) {
    if (!auth()->check() || !auth()->user()->is_admin) {
        abort(403, 'Unauthorized');
    }

    $user = User::where('username', $username)->first();
    
    if (!$user) {
        return response()->json(['error' => "User '{$username}' not found"], 404);
    }

    $radCheck = RadCheck::where('username', $username)->get(['attribute', 'op', 'value']);
    $radUserGroup = RadUserGroup::where('username', $username)->first();
    $activeSessions = RadAcct::forUser($username)->whereNull('acctstoptime')->get([
        'acctsessionid', 'framedipaddress', 'callingstationid', 'acctstarttime', 
        'acctinputoctets', 'acctoutputoctets'
    ]);
    
    $groupReplies = [];
    if ($radUserGroup) {
        $groupReplies = RadGroupReply::where('groupname', $radUserGroup->groupname)
            ->get(['attribute', 'op', 'value']);
    }

    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'plan_id' => $user->plan_id,
            'plan_name' => $user->plan ? $user->plan->name : null,
            'max_devices' => $user->plan ? $user->plan->max_devices : null,
        ],
        'radcheck' => $radCheck,
        'radusergroup' => $radUserGroup ? [
            'groupname' => $radUserGroup->groupname,
            'priority' => $radUserGroup->priority,
        ] : null,
        'radgroupreply' => $groupReplies,
        'active_sessions' => $activeSessions,
        'active_sessions_count' => $activeSessions->count(),
    ], 200, [], JSON_PRETTY_PRINT);
})->name('radius.diagnostic');

Route::get('/admin/radius-fix/{username}', function ($username) {
    if (!auth()->check() || !auth()->user()->is_admin) {
        abort(403, 'Unauthorized');
    }

    $user = User::where('username', $username)->first();
    
    if (!$user) {
        return response()->json(['error' => "User '{$username}' not found"], 404);
    }

    // Clear existing RADIUS entries
    RadCheck::where('username', $username)->delete();
    RadUserGroup::where('username', $username)->delete();

    // Re-sync
    \App\Services\PlanSyncService::syncUserPlan($user);

    return response()->json([
        'message' => "RADIUS configuration re-synced for {$username}",
        'radcheck_entries' => RadCheck::where('username', $username)->get(['attribute', 'value']),
        'radusergroup' => RadUserGroup::where('username', $username)->first(),
    ], 200, [], JSON_PRETTY_PRINT);
})->name('radius.fix');
