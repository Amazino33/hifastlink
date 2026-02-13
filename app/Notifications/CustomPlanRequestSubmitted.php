<?php

namespace App\Notifications;

use App\Models\CustomPlanRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomPlanRequestSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public CustomPlanRequest $request;

    /**
     * Create a new notification instance.
     */
    public function __construct(CustomPlanRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/admin/custom-plan-requests/' . $this->request->id . '/edit');

        // Load relationships if not already loaded
        $request = $this->request->load(['user', 'router']);

        return (new MailMessage)
            ->subject('New Custom Data Plan Request')
            ->greeting('Hello Admin!')
            ->line('A new custom data plan request has been submitted.')
            ->line('**Requester:** ' . ($request->user->name ?? 'Unknown User'))
            ->line('**Router:** ' . ($request->router->name ?? 'Unknown Router') . ' (' . ($request->router->location ?? 'Unknown Location') . ')')
            ->line('**Number of Plans:** ' . count($request->requested_plans))
            ->line('**Show Universal Plans:** ' . ($request->show_universal_plans ? 'Yes' : 'No'))
            ->action('Review Request', $url)
            ->line('Please review and approve or reject this request.')
            ->salutation('Best regards, FastLink System');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Load relationships if not already loaded
        $request = $this->request->load(['user', 'router']);

        return [
            'request_id' => $request->id,
            'user_name' => $request->user->name ?? 'Unknown User',
            'router_name' => $request->router->name ?? 'Unknown Router',
            'plans_count' => count($request->requested_plans),
        ];
    }
}