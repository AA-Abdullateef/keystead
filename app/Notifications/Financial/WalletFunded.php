<?php

namespace App\Notifications\Financial;

use App\Notifications\FinancialNotification;
use Illuminate\Notifications\Messages\MailMessage;

class WalletFunded extends FinancialNotification
{
    public function __construct(public string $amount, public string $balanceAfter)
    {
    }

    protected function subject(): string
    {
        return 'Wallet funded';
    }

    protected function lines(MailMessage $mail): MailMessage
    {
        return $mail
            ->line('An administrator recorded a deposit of ' . $this->usd($this->amount) . ' to your wallet.')
            ->line('Your new balance is ' . $this->usd($this->balanceAfter) . '.');
    }
}
