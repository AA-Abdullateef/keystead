<?php

namespace App\Notifications\Financial;

use App\Notifications\FinancialNotification;
use Illuminate\Notifications\Messages\MailMessage;

class LoanApplicationSubmitted extends FinancialNotification
{
    public function __construct(public string $type, public string $property, public string $initialPayment, public string $monthly, public string $total, public int $months)
    {
    }

    protected function subject(): string
    {
        return ucfirst($this->type) . ' loan application submitted';
    }

    protected function lines(MailMessage $mail): MailMessage
    {
        return $mail
            ->line('We received your ' . $this->type . ' loan application for **' . $this->property . '**.')
            ->line('Your initial payment of ' . $this->usd($this->initialPayment) . ' has been taken from your wallet and is refunded if the application is rejected or cancelled.')
            ->line('If approved: ' . $this->months . ' monthly installments of ' . $this->usd($this->monthly) . ' (total payable ' . $this->usd($this->total) . ').');
    }
}
