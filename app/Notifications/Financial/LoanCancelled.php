<?php

namespace App\Notifications\Financial;

use App\Notifications\FinancialNotification;
use Illuminate\Notifications\Messages\MailMessage;

class LoanCancelled extends FinancialNotification
{
    public function __construct(public string $type, public string $property, public string $refund)
    {
    }

    protected function subject(): string
    {
        return ucfirst($this->type) . ' loan application cancelled';
    }

    protected function lines(MailMessage $mail): MailMessage
    {
        return $mail
            ->line('Your ' . $this->type . ' loan application for **' . $this->property . '** was cancelled.')
            ->line('Your initial payment of ' . $this->usd($this->refund) . ' has been refunded to your wallet.');
    }
}
