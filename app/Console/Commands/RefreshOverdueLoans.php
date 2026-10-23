<?php

namespace App\Console\Commands;

use App\Services\LoanService;
use Illuminate\Console\Command;

class RefreshOverdueLoans extends Command
{
    protected $signature = 'loans:refresh-overdue';

    protected $description = 'Flag active mortgage and property loans that have fallen behind their repayment schedule as overdue';

    public function handle(LoanService $loans): int
    {
        $this->info($loans->refreshOverdueLoans() . ' loan(s) newly flagged overdue.');

        return self::SUCCESS;
    }
}
