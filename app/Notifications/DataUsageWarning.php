<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DataUsageWarning extends Notification implements ShouldQueue
{
    use Queueable;

    public float $usagePercent;

    public function __construct(float $usagePercent)
    {
        $this->usagePercent = $usagePercent;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('HiFastLink - Data Usage Warning')
                    ->greeting("Hi {$notifiable->name},")
                    ->line("Your data usage has reached {$this->usagePercent}% of your plan limit.")
                    ->line("Used: " . number_format($notifiable->data_used / 1024 / 1024 / 1024, 2) . " GB")
                    ->line("Limit: " . number_format($notifiable->data_limit / 1024 / 1024 / 1024, 2) . " GB")
                    ->action('Upgrade Plan', url('/subscriptions'))
                    ->line('Consider upgrading your plan to avoid service interruption.')
                    ->salutation('Best regards, HiFastLink Team');
    }
}