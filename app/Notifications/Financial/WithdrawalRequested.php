<?php

namespace App\Notifications\Financial;

use App\Notifications\FinancialNotification;
use Illuminate\Notifications\Messages\MailMessage;

class WithdrawalRequested extends FinancialNotification
{
    public function __construct(public string $amount)
    {
    }

    protected function subject(): string
    {
        return 'Withdrawal request received';
    }

    protected function lines(MailMessage $mail): MailMessage
    {
        return $mail
            ->line('We received your withdrawal request for ' . $this->usd($this->amount) . '.')
            ->line('The amount is reserved in your wallet while it is reviewed. You will be told when it is approved or rejected.');
    }
}
