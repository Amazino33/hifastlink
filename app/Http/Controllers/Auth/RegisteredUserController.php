<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FreeTrialService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:'.User::class, 'alpha_num'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone'    => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'              => $request->name,
            'username'          => $request->username,
            'email'             => $request->email,
            'phone'             => $request->phone,
            'password'          => Hash::make($request->password),
            'radius_password'   => $request->password,
            'data_limit'        => 1000000000,
            'connection_status' => 'active',
        ]);

        event(new Registered($user));
        Auth::login($user);

        if ($request->input('bonus') === 'free_trial') {
            $routerIdentifier = $request->input('router');
            FreeTrialService::apply($user, $routerIdentifier);

            if ($routerIdentifier) {
                return redirect()->route('dashboard', ['router' => $routerIdentifier]);
            }
        }

        return redirect(route('dashboard', absolute: false));
    }
}
