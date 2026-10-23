<?php

namespace App\Notifications\Financial;

use App\Notifications\FinancialNotification;
use Illuminate\Notifications\Messages\MailMessage;

class InvestmentConfirmed extends FinancialNotification
{
    public function __construct(public string $property, public string $amount, public string $totalInvested, public string $ownershipPercent)
    {
    }

    protected function subject(): string
    {
        return 'Investment confirmed';
    }

    protected function lines(MailMessage $mail): MailMessage
    {
        return $mail
            ->line('Your investment of ' . $this->usd($this->amount) . ' in **' . $this->property . '** was successful.')
            ->line('Your total in this project is now ' . $this->usd($this->totalInvested) . ' (' . rtrim(rtrim($this->ownershipPercent, '0'), '.') . '% of the funding target).')
            ->line('Returns are not guaranteed; profit is distributed only from rental income actually received.');
    }
}
