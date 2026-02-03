<?php

namespace App\Http\Controllers;

use App\Models\RadCheck;
use App\Jobs\RestoreRadCheck;
use App\Mail\MagicLoginMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class RouterAuthController extends Controller
{
    public function sendMagicLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return back()->withErrors(['email' => 'No user found for that email.']);
        }

        $token = Str::random(48);
        Cache::put("magiclogin:{$token}:uid", $user->id, now()->addMinutes(30));

        $url = URL::temporarySignedRoute('router.magic_login', now()->addMinutes(30), ['token' => $token]);

        \Mail::to($user->email)->send(new MagicLoginMail($user, $url));

        Log::info("Magic login link generated for user {$user->id}");

        return back()->with('success', 'Magic login link sent to your email.');
    }

    public function handleMagicLogin(Request $request)
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $token = $request->query('token');
        $userId = Cache::get("magiclogin:{$token}:uid");
        if (! $userId) {
            return redirect()->route('login')->withErrors(['error' => 'This magic link has expired or is invalid.']);
        }

        $user = User::find($userId);
        if (! $user) {
            return redirect()->route('login')->withErrors(['error' => 'User not found.']);
        }

        // Log the user into the site
        Auth::loginUsingId($user->id);

        // Create temporary password and update RadCheck
        $tempPassword = Str::random(12);
        $old = RadCheck::where('username', $user->username)->value('value');

        RadCheck::updateOrCreate([
            'username' => $user->username,
        ], [
            'attribute' => 'Cleartext-Password',
            'op' => ':=',
            'value' => $tempPassword,
        ]);

        // Call bridge to log user into router
        try {
            $bridgeUrl = rtrim(env('RADIUS_BRIDGE_URL', ''), '/');
            $secret = env('RADIUS_SECRET_KEY', null);

            if ($bridgeUrl && $secret) {
                $resp = Http::post($bridgeUrl . '/login', [
                    'username' => $user->username,
                    'password' => $tempPassword,
                    'secret' => $secret,
                ]);

                Log::info("Magic router login for {$user->username}", ['resp' => $resp->body()]);
            }
        } catch (\Exception $e) {
            Log::error("Magic router login failed for {$user->username}: " . $e->getMessage());
        }

        // Schedule restore of old password in 10 minutes
        RestoreRadCheck::dispatch($user->username, $old)->delay(now()->addMinutes(10));

        // Cleanup token
        Cache::forget("magiclogin:{$token}:uid");

        return redirect()->route('dashboard')->with('success', 'You are logged in to the site and router (temporary).');
    }
}
