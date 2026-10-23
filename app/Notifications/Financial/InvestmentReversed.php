<?php

namespace App\Notifications\Financial;

use App\Notifications\FinancialNotification;
use Illuminate\Notifications\Messages\MailMessage;

class InvestmentReversed extends FinancialNotification
{
    public function __construct(public string $property, public string $amount)
    {
    }

    protected function subject(): string
    {
        return 'Investment reversed';
    }

    protected function lines(MailMessage $mail): MailMessage
    {
        return $mail
            ->line('Your investment in **' . $this->property . '** was reversed by an administrator.')
            ->line($this->usd($this->amount) . ' has been returned to your wallet.');
    }
}
