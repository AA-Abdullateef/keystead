<?php

namespace App\Notifications\Financial;

use App\Notifications\FinancialNotification;
use Illuminate\Notifications\Messages\MailMessage;

class WithdrawalApproved extends FinancialNotification
{
    public function __construct(public string $amount)
    {
    }

    protected function subject(): string
    {
        return 'Withdrawal approved';
    }

    protected function lines(MailMessage $mail): MailMessage
    {
        return $mail
            ->line('Your withdrawal of ' . $this->usd($this->amount) . ' has been approved.');
    }
}
