<?php

namespace App\Notifications\Financial;

use App\Notifications\FinancialNotification;
use Illuminate\Notifications\Messages\MailMessage;

class LoanPaymentReceived extends FinancialNotification
{
    public function __construct(public string $type, public string $property, public string $amount, public string $remaining, public bool $completed)
    {
    }

    protected function subject(): string
    {
        return 'Loan payment received';
    }

    protected function lines(MailMessage $mail): MailMessage
    {
        return $mail
            ->line('We received your ' . $this->type . ' loan installment of ' . $this->usd($this->amount) . ' for **' . $this->property . '**.')
            ->line($this->completed ? 'Your loan is now fully repaid. Congratulations!' : 'Remaining balance: ' . $this->usd($this->remaining) . '.');
    }
}
