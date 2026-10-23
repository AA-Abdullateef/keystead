<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base for account-security e-mails (verification, reset, password and status changes).
 * Deliberately NOT queued: they go out synchronously, so a reset link or a "your password changed"
 * warning never waits on, or is lost with, a worker.
 */
abstract class SecurityNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    abstract protected function subject(): string;

    abstract protected function lines(MailMessage $mail): MailMessage;

    public function toMail(object $notifiable): MailMessage
    {
        return $this->lines(
            (new MailMessage)->subject('[' . config('app.name') . '] ' . $this->subject())
                ->greeting('Hello ' . ($notifiable->name ?? '') . ',')
        );
    }
}
