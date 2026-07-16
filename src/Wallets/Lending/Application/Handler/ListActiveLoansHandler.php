<?php

namespace Wallets\Lending\Application\Handler;

use App\Models\Loan;
use Wallets\Lending\Application\LoanPaymentScheduleService;
use Wallets\Lending\Application\Query\ListActiveLoans;
use Wallets\Lending\Domain\AmortizationCalculator;
use Wallets\Shared\Application\Query;
use Wallets\Shared\Application\QueryHandler;

final class ListActiveLoansHandler implements QueryHandler
{
    public function __construct(
        private readonly LoanPaymentScheduleService $paymentSchedule,
        private readonly AmortizationCalculator $amortization,
    ) {}

    public function handle(Query $query): mixed
    {
        assert($query instanceof ListActiveLoans);

        $loans = Loan::with(['payments', 'wallet', 'recurringItem'])
            ->forUser($query->userId)
            ->where('is_settled', false)
            ->get();

        $loans->transform(function ($loan) {
            $totalPaid = $loan->payments->sum('amount');

            if ($loan->type === 'bank') {
                $schedule = $this->amortization->calculate(
                    $loan->id,
                    $loan->principal_amount,
                    $loan->interest_rate,
                    $loan->term_months,
                    $loan->started_at,
                    $loan->monthly_payment,
                    $loan->interest_calculation_method ?? 'monthly'
                );

                if ($schedule->isEmpty()) {
                    $loan->remaining_months = $loan->term_months;
                    $loan->remaining_principal = $loan->principal_amount;
                    $loan->remaining_interest = 0;
                } else {
                    $monthsPassed = $this->paymentSchedule->effectiveMonthsPaid($loan, $schedule, $loan->payments);
                    $maxIndex = $schedule->max('month_index');

                    $loan->remaining_months = max(0, ($loan->term_months ?? $maxIndex) - $monthsPassed);
                    $loan->remaining_principal = $this->paymentSchedule->remainingPrincipalAt($loan, $schedule, $monthsPassed);
                    $loan->remaining_interest = $schedule->where('month_index', '>', $monthsPassed)->sum('interest');
                }
            } else {
                $loan->remaining_amount = $loan->principal_amount - $totalPaid;
            }

            $loan->payoff_remaining = $loan->type === 'bank'
                ? ($loan->remaining_principal ?? 0)
                : ($loan->remaining_amount ?? 0);

            return $loan;
        });

        $totalRemaining = $loans->reduce(function ($carry, $loan) {
            if ($loan->type === 'bank') {
                return $carry + ($loan->remaining_principal ?? 0);
            }
            if ($loan->type === 'borrow') {
                return $carry + ($loan->remaining_amount ?? 0);
            }

            return $carry;
        }, 0);

        $totalLendRemaining = $loans->reduce(function ($carry, $loan) {
            if ($loan->type === 'lend') {
                $paid = $loan->payments->sum('amount');

                return $carry + max(($loan->principal_amount - $paid), 0);
            }

            return $carry;
        }, 0);

        return compact('loans', 'totalRemaining', 'totalLendRemaining');
    }
}
