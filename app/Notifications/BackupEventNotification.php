<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Mail notification for Backup Manager events (on-demand recipients, no notifications table needed). */
class BackupEventNotification extends Notification
{
    public function __construct(public string $subject, public string $body, public string $level = 'info')
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('[' . config('app.name') . '] ' . $this->subject)
            ->line($this->body)
            ->line('Time: ' . now()->toDateTimeString());

        return $this->level === 'error' ? $mail->error() : $mail;
    }
}
