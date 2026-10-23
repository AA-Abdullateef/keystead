<?php

namespace App\Notifications\Security;

use App\Notifications\SecurityNotification;
use Illuminate\Notifications\Messages\MailMessage;

class AccountStatusChanged extends SecurityNotification
{
    public function __construct(public string $status)
    {
    }

    protected function subject(): string
    {
        return 'Your account status changed';
    }

    protected function lines(MailMessage $mail): MailMessage
    {
        return $mail
            ->line($this->status === 'suspended'
                ? 'Your account has been suspended. You cannot sign in or move money until it is reactivated.'
                : 'Your account has been reactivated. You can sign in again.');
    }
}
