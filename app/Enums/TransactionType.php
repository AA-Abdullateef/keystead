<?php

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Investment = 'investment';
    case InvestmentReversal = 'investment_reversal';
    case ProfitDistribution = 'profit_distribution';
    case Withdrawal = 'withdrawal';
    case WithdrawalRefund = 'withdrawal_refund';
    case LoanInitialPayment = 'loan_initial_payment';
    case LoanRefund = 'loan_refund';
    case LoanPayment = 'loan_payment';
}
