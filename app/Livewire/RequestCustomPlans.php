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
            'data_limit' => '',
            'duration_days' => 30,
            'price' => '',
            'speed_limit' => '',
            'max_devices' => '',
        ]
    ];

    public $successMessage = '';
    public $errorMessage = '';

    protected $rules = [
        'router_id' => 'required|exists:routers,id',
        'show_universal_plans' => 'boolean',
        'plans' => 'required|array|min:1',
        'plans.*.name' => 'required|string|max:255',
        'plans.*.data_limit' => 'required|integer|min:1',
        'plans.*.duration_days' => 'required|integer|min:1',
        'plans.*.price' => 'required|numeric|min:0',
        'plans.*.speed_limit' => 'nullable|string|max:255',
        'plans.*.max_devices' => 'nullable|integer|min:1',
    ];

    public function mount()
    {
        // Try to pre-select the user's current router
        $user = Auth::user();
        if ($user && method_exists($user, 'getCurrentRouter')) {
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
            'data_limit' => '',
            'duration_days' => 30,
            'price' => '',
            'speed_limit' => '',
            'max_devices' => '',
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

        try {
            $this->validate();

            // Check if user already has a pending request for this router
            $existingRequest = CustomPlanRequest::where('user_id', Auth::id())
                ->where('router_id', $this->router_id)
                ->where('status', 'pending')
                ->first();

            if ($existingRequest) {
                $this->addError('router_id', 'You already have a pending request for this router.');
                return;
            }

            $request = CustomPlanRequest::create([
                'user_id' => Auth::id(),
                'router_id' => $this->router_id,
                'show_universal_plans' => $this->show_universal_plans,
                'requested_plans' => $this->plans,
            ]);

            // Send notification to admins (if any exist)
            try {
                $admins = \App\Models\User::where(function ($query) {
                    $query->where('is_admin', true)
                          ->orWhere('email', 'amazino33@gmail.com')
                          ->orWhereHas('roles', function ($roleQuery) {
                              $roleQuery->whereIn('name', ['super_admin', 'admin']);
                          });
                })->get();

                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new CustomPlanRequestSubmitted($request));
                }
            } catch (\Exception $notificationError) {
                // Log notification error but don't fail the request
                \Log::warning('Failed to send admin notification: ' . $notificationError->getMessage(), [
                    'request_id' => $request->id,
                    'admin_count' => $admins->count() ?? 0
                ]);
            }

            $this->successMessage = 'Your custom plan request has been submitted successfully!';

            // Reset form
            $this->reset(['plans', 'show_universal_plans']);
            $this->plans = [
                [
                    'name' => '',
                    'data_limit' => '',
                    'duration_days' => 30,
                    'price' => '',
                    'speed_limit' => '',
                    'max_devices' => '',
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