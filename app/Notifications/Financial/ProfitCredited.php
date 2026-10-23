<?php

namespace App\Notifications\Financial;

use App\Notifications\FinancialNotification;
use Illuminate\Notifications\Messages\MailMessage;

class ProfitCredited extends FinancialNotification
{
    public function __construct(public string $property, public string $amount, public int $month, public int $year)
    {
    }

    protected function subject(): string
    {
        return 'Profit credited to your wallet';
    }

    protected function lines(MailMessage $mail): MailMessage
    {
        return $mail
            ->line($this->usd($this->amount) . ' of profit for **' . $this->property . '** (' . sprintf('%02d/%d', $this->month, $this->year) . ') has been credited to your wallet.');
    }
}
