<?php

namespace App\Notifications;

use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base for transactional money e-mails. Sent immediately (not queued) and carry only scalars, never
 * models, so a reversed/deleted record cannot break them.
 */
abstract class FinancialNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    abstract protected function subject(): string;

    abstract protected function lines(MailMessage $mail): MailMessage;

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('[' . config('app.name') . '] ' . $this->subject())
            ->greeting('Hello ' . ($notifiable->name ?? '') . ',');

        return $this->lines($mail)->line('This is an automated message about activity on your ' . config('app.name') . ' account.');
    }

    protected function usd(string $amount): string
    {
        return '$' . Money::display($amount);
    }
}
