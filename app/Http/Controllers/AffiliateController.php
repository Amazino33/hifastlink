<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Router;

class AffiliateController extends Controller
{
    public function routerAnalytics(Request $request)
    {
        $user = Auth::user();
        if (! $user || ! method_exists($user, 'hasRole') || ! $user->hasRole('affiliate')) {
            abort(403, 'Access denied.');
        }

        $routerId = $user->router_id;
        if (! $routerId) {
            abort(404, 'No router assigned to your account.');
        }

        $router = Router::find($routerId);
        if (! $router) {
            abort(404, 'Router not found.');
        }

        return view('affiliate.router-analytics', compact('router'));
    }
}
