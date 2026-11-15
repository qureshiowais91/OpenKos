<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantInvitation extends Notification
{
    use Queueable;

    public function __construct(private readonly string $url) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $brand = (string) Setting::get('site_name', config('app.name'));

        return (new MailMessage)
            ->subject(__('notifications.tenant_invitation.subject', ['app' => $brand]))
            ->greeting(__('notifications.tenant_invitation.greeting', ['app' => $brand]))
            ->line(__('notifications.tenant_invitation.intro'))
            ->line(__('notifications.tenant_invitation.instruction'))
            ->action(__('notifications.tenant_invitation.action'), $this->url)
            ->line(__('notifications.tenant_invitation.outro'));
    }

    public function toArray(object $notifiable): array
    {
        return ['url' => $this->url];
    }
}
