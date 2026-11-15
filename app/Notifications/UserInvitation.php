<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitation extends Notification
{
    use Queueable;

    public function __construct(private readonly string $url) {}

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
        $brand = (string) Setting::get('site_name', config('app.name'));

        return (new MailMessage)
            ->subject(__('You are invited to :app', ['app' => $brand]))
            ->greeting(__('Welcome to :app', ['app' => $brand]))
            ->line(__('You have been invited to manage properties in :app.', ['app' => $brand]))
            ->line(__('Accept the invitation to set your password and activate your account.'))
            ->action(__('Accept Invitation'), $this->url)
            ->line(__('If you did not expect this invitation, you may ignore this email.'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['url' => $this->url];
    }
}
