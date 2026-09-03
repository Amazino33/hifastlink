<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PlanSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user        = $request->user();
        $isOAuthUser = (bool) $user->google_id;

        // Google OAuth users have no known password — skip current_password check
        $rules = ['password' => ['required', Password::defaults(), 'confirmed']];
        if (! $isOAuthUser) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $validated = $request->validateWithBag('updatePassword', $rules);

        $user->update([
            'password'        => Hash::make($validated['password']),
            'radius_password' => $validated['password'],
        ]);

        // Push the updated password to radcheck immediately.
        PlanSyncService::syncUserPlan($user->fresh());

        return back()->with('status', 'password-updated');
    }
}
