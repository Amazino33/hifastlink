<?php

namespace App\Livewire;

use App\Models\CustomPlanRequest;
use App\Models\Router;
use App\Notifications\CustomPlanRequestSubmitted;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class RequestCustomPlans extends Component
{
    public $router_id;
    public $show_universal_plans = false;
    public $plans = [
        [
            'name' => '',
            'description' => '',
            'price' => '',
            'data_limit' => '',
            'time_limit' => '',
            'speed_limit_upload' => '',
            'speed_limit_download' => '',
            'validity_days' => 30,
            'speed_limit' => '',
            'allowed_login_time' => '',
            'limit_unit' => 'MB',
            'max_devices' => '',
            'features' => '',
        ]
    ];

    public $successMessage = '';
    public $errorMessage = '';
    public $showSuccessMessage = false;
    public $showErrorMessage = false;

    protected $rules = [
        'router_id' => 'required|exists:routers,id',
        'show_universal_plans' => 'boolean',
        'plans' => 'required|array|min:1',
        'plans.*.name' => 'required|string|max:255',
        'plans.*.description' => 'nullable|string|max:1000',
        'plans.*.price' => 'required|numeric|min:0',
        'plans.*.data_limit' => 'required|integer|min:1',
        'plans.*.time_limit' => 'nullable|integer|min:1',
        'plans.*.speed_limit_upload' => 'nullable|integer|min:1',
        'plans.*.speed_limit_download' => 'nullable|integer|min:1',
        'plans.*.validity_days' => 'required|integer|min:1',
        'plans.*.speed_limit' => 'nullable|string|max:255',
        'plans.*.allowed_login_time' => 'nullable|string|max:255',
        'plans.*.limit_unit' => 'required|in:MB,GB',
        'plans.*.max_devices' => 'nullable|integer|min:1',
        'plans.*.features' => 'nullable|string|max:2000',
    ];

    public function mount()
    {
        // Authorization: only affiliates may request custom plans
        $user = Auth::user();
        $isAffiliate = $user && method_exists($user, 'hasRole') && $user->hasRole('affiliate');

        if (! $user || ! $isAffiliate) {
            abort(403, 'Access denied. Affiliates only.');
        }

        // Try to pre-select the user's current router (affiliates often have router_id set)
        if (method_exists($user, 'getCurrentRouter')) {
            $currentRouter = $user->getCurrentRouter();
            if ($currentRouter) {
                $this->router_id = $currentRouter->id;
            }
        }

    }

    public function addPlan()
    {
        $this->plans[] = [
            'name' => '',
            'description' => '',
            'price' => '',
            'data_limit' => '',
            'time_limit' => '',
            'speed_limit_upload' => '',
            'speed_limit_download' => '',
            'validity_days' => 30,
            'speed_limit' => '',
            'allowed_login_time' => '',
            'limit_unit' => 'MB',
            'max_devices' => '',
            'features' => '',
        ];
    }

    public function removePlan($index)
    {
        if (count($this->plans) > 1) {
            unset($this->plans[$index]);
            $this->plans = array_values($this->plans);
        }
    }

    public function submitRequest()
    {
        // Clear any previous messages
        $this->successMessage = '';
        $this->errorMessage = '';
        $this->showSuccessMessage = false;
        $this->showErrorMessage = false;

        \Log::info('Starting custom plan request submission', [
            'user_id' => Auth::id(),
            'router_id' => $this->router_id,
            'plans_count' => count($this->plans)
        ]);

        try {
            $this->validate();

            \Log::info('Validation passed');

            // Clean up the plans data - convert empty strings to null for numeric fields, provide defaults for required string fields
            $cleanedPlans = [];
            foreach ($this->plans as $plan) {
                $cleanedPlans[] = [
                    'name' => $plan['name'],
                    'description' => $plan['description'] ?: null,
                    'price' => $plan['price'],
                    'data_limit' => $plan['data_limit'],
                    'time_limit' => $plan['time_limit'] ?: null,
                    'speed_limit_upload' => $plan['speed_limit_upload'] ?: null,
                    'speed_limit_download' => $plan['speed_limit_download'] ?: null,
                    'validity_days' => $plan['validity_days'],
                    'speed_limit' => $plan['speed_limit'] ?: '10M/10M', // Provide default for non-nullable field
                    'allowed_login_time' => $plan['allowed_login_time'] ?: null,
                    'limit_unit' => $plan['limit_unit'],
                    'max_devices' => $plan['max_devices'] ?: null,
                    'features' => $plan['features'] ?: null,
                ];
            }

            // Check if user already has a pending request for this router
            $existingRequest = CustomPlanRequest::where('user_id', Auth::id())
                ->where('router_id', $this->router_id)
                ->where('status', 'pending')
                ->first();

            if ($existingRequest) {
                $this->addError('router_id', 'You already have a pending request for this router.');
                return;
            }

            \Log::info('No existing pending request found, creating new request');

            $request = CustomPlanRequest::create([
                'user_id' => Auth::id(),
                'router_id' => $this->router_id,
                'show_universal_plans' => $this->show_universal_plans,
                'requested_plans' => $cleanedPlans,
            ]);

            \Log::info('Request created successfully', ['request_id' => $request->id]);

            // Send notification to admins (if any exist)
            try {
                $admins = \App\Models\User::where(function ($query) {
                    $query->where('email', 'amazino33@gmail.com')
                          ->orWhereHas('roles', function ($roleQuery) {
                              $roleQuery->whereIn('name', ['super_admin', 'admin']);
                          });
                })->get();

                \Log::info('Found admins for notification', ['admin_count' => $admins->count()]);

                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new CustomPlanRequestSubmitted($request));
                    \Log::info('Admin notification sent successfully');
                }
            } catch (\Exception $notificationError) {
                // Log notification error but don't fail the request
                \Log::warning('Failed to send admin notification: ' . $notificationError->getMessage(), [
                    'request_id' => $request->id,
                    'admin_count' => $admins->count() ?? 0
                ]);
            }

            $this->successMessage = 'Your custom plan request has been submitted successfully!';
            $this->showSuccessMessage = true;

            \Log::info('Success message set, resetting form');

            // Reset form
            $this->reset(['plans', 'show_universal_plans']);
            $this->plans = [
                [
                    'name' => '',
                    'description' => '',
                    'price' => '',
                    'data_limit' => '',
                    'time_limit' => '',
                    'speed_limit_upload' => '',
                    'speed_limit_download' => '',
                    'validity_days' => 30,
                    'speed_limit' => '',
                    'allowed_login_time' => '',
                    'limit_unit' => 'MB',
                    'max_devices' => '',
                    'features' => '',
                ]
            ];
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Custom plan request submission failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'router_id' => $this->router_id ?? null,
                'plans' => $this->plans ?? [],
                'trace' => $e->getTraceAsString()
            ]);

            // Show user-friendly error message
            $this->errorMessage = 'An error occurred while submitting your request. Please try again or contact support.';
            $this->showErrorMessage = true;
        }
    }

    public function hideMessage($type)
    {
        if ($type === 'success') {
            $this->showSuccessMessage = false;
            $this->successMessage = '';
        } elseif ($type === 'error') {
            $this->showErrorMessage = false;
            $this->errorMessage = '';
        }
    }

    public function render()
    {
        $routers = Router::active()->get();

        return view('livewire.request-custom-plans', [
            'routers' => $routers,
        ]);
    }
}