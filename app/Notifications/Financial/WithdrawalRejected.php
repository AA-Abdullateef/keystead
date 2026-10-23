<?php

namespace App\Notifications\Financial;

use App\Notifications\FinancialNotification;
use Illuminate\Notifications\Messages\MailMessage;

class WithdrawalRejected extends FinancialNotification
{
    public function __construct(public string $amount)
    {
    }

    protected function subject(): string
    {
        return 'Withdrawal rejected — funds returned';
    }

    protected function lines(MailMessage $mail): MailMessage
    {
        return $mail
            ->line('Your withdrawal request for ' . $this->usd($this->amount) . ' was not approved.')
            ->line('The full amount has been refunded to your wallet.');
    }
}
