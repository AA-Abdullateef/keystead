<?php

namespace App\Notifications\Security;

use App\Notifications\SecurityNotification;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyRegistration extends SecurityNotification
{
    public function __construct(public string $url, public int $hours)
    {
    }

    protected function subject(): string
    {
        return 'Confirm your email address';
    }

    protected function lines(MailMessage $mail): MailMessage
    {
        return $mail
            ->line('Welcome to ' . config('app.name') . '. Please confirm your email address to start investing and borrowing.')
            ->action('Confirm email address', $this->url)
            ->line('This link works once and expires in ' . $this->hours . ' hours. If you did not create an account, ignore this email.');
    }
}
