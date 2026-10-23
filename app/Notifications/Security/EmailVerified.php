<?php

namespace App\Notifications\Security;

use App\Notifications\SecurityNotification;
use Illuminate\Notifications\Messages\MailMessage;

class EmailVerified extends SecurityNotification
{
    protected function subject(): string
    {
        return 'Email address confirmed';
    }

    protected function lines(MailMessage $mail): MailMessage
    {
        return $mail
            ->line('Your email address is confirmed. Your account is fully active.');
    }
}
