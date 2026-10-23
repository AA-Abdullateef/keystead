<?php

namespace App\Notifications\Financial;

use App\Notifications\FinancialNotification;
use Illuminate\Notifications\Messages\MailMessage;

class LoanApproved extends FinancialNotification
{
    public function __construct(public string $type, public string $property, public string $monthly, public int $months)
    {
    }

    protected function subject(): string
    {
        return ucfirst($this->type) . ' loan approved';
    }

    protected function lines(MailMessage $mail): MailMessage
    {
        return $mail
            ->line('Good news: your ' . $this->type . ' loan for **' . $this->property . '** has been approved.')
            ->line('Repayment is ' . $this->months . ' monthly installments of ' . $this->usd($this->monthly) . ', one per calendar month, paid from your wallet.');
    }
}
