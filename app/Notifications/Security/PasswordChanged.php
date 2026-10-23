<?php

namespace App\Notifications\Security;

use App\Notifications\SecurityNotification;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordChanged extends SecurityNotification
{
    public function __construct(public string $via, public string $ip)
    {
    }

    protected function subject(): string
    {
        return 'Your password was changed';
    }

    protected function lines(MailMessage $mail): MailMessage
    {
        return $mail
            ->line('The password on your account was changed (' . $this->via . ', from IP ' . ($this->ip ?: 'unknown') . ').')
            ->line('All your API sessions were signed out. If this was not you, reset your password immediately and contact support.');
    }
}
