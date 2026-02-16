<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Number;
use App\Models\Router;
use App\Models\RadAcct;

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

        // Recent sessions for this router (read-only for affiliates)
        $sessionsQuery = RadAcct::query()->where(function ($q) use ($router) {
            $q->where('nasipaddress', $router->ip_address);

            if (Schema::hasColumn('radacct', 'nasidentifier')) {
                $q->orWhere('nasidentifier', $router->nas_identifier)
                  ->orWhere('nasidentifier', $router->identity ?? '');
            }
        });

        $recentSessions = $sessionsQuery->latest('acctstarttime')->limit(25)->get();

        return view('affiliate.router-analytics', compact('router', 'recentSessions'));
    }
}
