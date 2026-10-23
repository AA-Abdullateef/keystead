<?php

namespace App\Notifications\Security;

use App\Notifications\SecurityNotification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordLink extends SecurityNotification
{
    public function __construct(public string $url, public int $minutes)
    {
    }

    protected function subject(): string
    {
        return 'Reset your password';
    }

    protected function lines(MailMessage $mail): MailMessage
    {
        return $mail
            ->line('We received a request to reset your password.')
            ->action('Choose a new password', $this->url)
            ->line('This link works once and expires in ' . $this->minutes . ' minutes. If you did not ask for this, you can ignore this email — your password has not changed.');
    }
}
