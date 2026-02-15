<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormMail;

class PageController extends Controller
{
    /**
     * Display the pricing page with plans grouped by duration.
     */
    public function pricing(Request $request)
    {
        // Determine router context (user -> query param -> session) and use PlanFilterService
        $routerIdentifier = null;
        $routerId = null;
        $currentRouter = null;

        $user = auth()->user();
        if ($user && $user->router_id) {
            $currentRouter = $user->router;
            $routerIdentifier = $currentRouter?->nas_identifier;
            $routerId = $user->router_id;
        }

        // URL parameter can override
        if ($request->query('router')) {
            $urlRouter = \App\Models\Router::where('nas_identifier', $request->query('router'))->first();
            if ($urlRouter) {
                $currentRouter = $urlRouter;
                $routerIdentifier = $urlRouter->nas_identifier;
                $routerId = $urlRouter->id;
            }
        }

        // Session fallback
        if (!$routerIdentifier && session('current_router')) {
            $sessRouter = \App\Models\Router::where('nas_identifier', session('current_router'))->first();
            if ($sessRouter) {
                $currentRouter = $sessRouter;
                $routerIdentifier = $sessRouter->nas_identifier;
                $routerId = $sessRouter->id;
            }
        }

        $planFilter = new \App\Services\PlanFilterService();
        $plans = $planFilter->getAvailablePlans($routerIdentifier, $routerId);

        $plansByDuration = $plans->groupBy('validity_days')->sortKeys();

        return view('pricing', compact('plansByDuration', 'currentRouter'));
    }

    /**
     * Display the services page.
     */
    public function services()
    {
        return view('services');
    }

    /**
     * Display the contact form.
     */
    public function contact()
    {
        return view('contact');
    }

    /**
     * Handle contact form submission.
     */
    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        try {
            // Send email to admin
            Mail::to(config('mail.from.address'))->send(new ContactFormMail($validated));

            return back()->with('success', 'Thank you for contacting us! We will get back to you shortly.');
        } catch (\Exception $e) {
            return back()->with('error', 'Sorry, something went wrong. Please try again later or contact us directly.');
        }
    }

    /**
     * Display the coverage map page.
     */
    public function coverage()
    {
        return view('coverage');
    }

    /**
     * Display the help center page.
     */
    public function help()
    {
        return view('help');
    }

    /**
     * Display the FAQ page.
     */
    public function faq()
    {
        return view('faq');
    }

    /**
     * Display the installation guide page.
     */
    public function installation()
    {
        return view('installation');
    }

    /**
     * Display the network status page.
     */
    public function status()
    {
        return view('status');
    }
}
