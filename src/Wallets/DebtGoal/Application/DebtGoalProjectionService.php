<?php

namespace Wallets\DebtGoal\Application;

use App\Models\Loan;
use App\Models\Setting;
use App\Models\Wallet;
use Illuminate\Support\Collection;
use Wallets\DebtGoal\Domain\DebtGoalProgress;
use Wallets\DebtGoal\Domain\DebtGoalSettingKey;
use Wallets\DebtGoal\Domain\SettlementFeeSchedule;
use Wallets\Lending\Application\AmortizationService;
use Wallets\Lending\Application\LoanPaymentScheduleService;
use Wallets\Lending\Application\LoanScheduleGenerator;

final class DebtGoalProjectionService
{
    public function __construct(
        private readonly LoanPaymentScheduleService $paymentSchedule,
        private readonly AmortizationService $amortization,
        private readonly LoanScheduleGenerator $scheduleGenerator,
    ) {}

    public function feeScheduleForUser(int $userId): SettlementFeeSchedule
    {
        $raw = Setting::getForUser($userId, DebtGoalSettingKey::SETTLEMENT_FEES);

        return SettlementFeeSchedule::fromJson(is_string($raw) ? $raw : null);
    }

    public function resolveProgress(int $userId): ?array
    {
        $walletId = (int) Setting::getForUser($userId, DebtGoalSettingKey::WALLET_ID, 0);
        if ($walletId <= 0) {
            return null;
        }

        $wallet = Wallet::query()->forUser($userId)->where('id', $walletId)->first();
        if ($wallet === null) {
            return null;
        }

        $name = (string) Setting::getForUser($userId, DebtGoalSettingKey::NAME, 'Mục tiêu trả nợ');
        if (trim($name) === '') {
            $name = 'Mục tiêu trả nợ';
        }

        $loanId = (int) Setting::getForUser($userId, DebtGoalSettingKey::LOAN_ID, 0);
        $fees = $this->feeScheduleForUser($userId);

        if ($loanId > 0) {
            $loan = Loan::query()
                ->forUser($userId)
                ->where('id', $loanId)
                ->where('is_settled', false)
                ->first();

            if ($loan === null) {
                return null;
            }

            $snapshot = $this->currentSettlementSnapshot($loan, $fees);
            if ($snapshot === null || $snapshot['settlement_total'] <= 0) {
                return null;
            }

            return DebtGoalProgress::make(
                name: $name,
                targetAmount: $snapshot['settlement_total'],
                walletBalance: (float) $wallet->balance,
                walletId: (int) $wallet->id,
                walletName: (string) $wallet->name,
                loanId: (int) $loan->id,
                loanName: (string) $loan->name,
                principalRemaining: $snapshot['principal_remaining'],
                settlementInterest: $snapshot['settlement_interest'],
                settlementFee: $snapshot['settlement_fee'],
                currentYear: $snapshot['year'],
            )->toArray();
        }

        $targetAmount = (float) Setting::getForUser($userId, DebtGoalSettingKey::TARGET_AMOUNT, 0);
        if ($targetAmount <= 0) {
            return null;
        }

        return DebtGoalProgress::make(
            name: $name,
            targetAmount: $targetAmount,
            walletBalance: (float) $wallet->balance,
            walletId: (int) $wallet->id,
            walletName: (string) $wallet->name,
        )->toArray();
    }

    /**
     * @return array{
     *   loan: Loan,
     *   months_passed: int,
     *   payment_day: int|null,
     *   current: array{principal_remaining:float,settlement_interest:float,settlement_fee:float,settlement_total:float,year:int,month_index:int}|null,
     *   rows: list<array<string,mixed>>,
     *   fees: list<array{year:int,type:string,value:float}>,
     *   collected: float,
     *   wallet: Wallet|null
     * }|null
     */
    public function resolveDetail(int $userId): ?array
    {
        $loanId = (int) Setting::getForUser($userId, DebtGoalSettingKey::LOAN_ID, 0);
        if ($loanId <= 0) {
            return null;
        }

        $loan = Loan::query()
            ->forUser($userId)
            ->with(['payments', 'wallet', 'customSchedules'])
            ->where('id', $loanId)
            ->first();

        if ($loan === null || $loan->type !== 'bank') {
            return null;
        }

        $walletId = (int) Setting::getForUser($userId, DebtGoalSettingKey::WALLET_ID, 0);
        $wallet = $walletId > 0
            ? Wallet::query()->forUser($userId)->where('id', $walletId)->first()
            : null;
        $collected = $wallet ? max(0.0, (float) $wallet->balance) : 0.0;
        $fees = $this->feeScheduleForUser($userId);

        $built = $this->buildLoanSchedule($loan);
        if ($built === null) {
            return null;
        }

        ['schedule' => $schedule, 'months_passed' => $monthsPassed] = $built;
        $rows = $this->buildSettlementRows($loan, $schedule, $monthsPassed, $fees, $collected);
        $current = $this->currentSettlementSnapshot($loan, $fees, $schedule, $monthsPassed);

        return [
            'loan' => $loan,
            'months_passed' => $monthsPassed,
            'payment_day' => $this->paymentSchedule->paymentDay($loan),
            'current' => $current,
            'rows' => $rows,
            'fees' => $fees->toArray(),
            'collected' => $collected,
            'wallet' => $wallet,
        ];
    }

    /**
     * Tổng tất toán = dư nợ gốc + lãi kỳ tất toán + phí tất toán.
     *
     * @return array{principal_remaining:float,settlement_interest:float,settlement_fee:float,settlement_total:float,year:int,month_index:int}|null
     */
    public function currentSettlementSnapshot(
        Loan $loan,
        SettlementFeeSchedule $fees,
        ?Collection $schedule = null,
        ?int $monthsPassed = null,
    ): ?array {
        if ($loan->type !== 'bank') {
            $remaining = max(0.0, (float) $loan->principal_amount - (float) $loan->payments()->sum('amount'));
            $year = 1;
            $fee = $fees->feeForYear($year, $remaining);

            return [
                'principal_remaining' => $remaining,
                'settlement_interest' => 0.0,
                'settlement_fee' => $fee,
                'settlement_total' => $remaining + $fee,
                'year' => $year,
                'month_index' => 0,
            ];
        }

        if ($schedule === null || $monthsPassed === null) {
            $built = $this->buildLoanSchedule($loan);
            if ($built === null) {
                return null;
            }
            $schedule = $built['schedule'];
            $monthsPassed = $built['months_passed'];
        }

        $remaining = $this->paymentSchedule->remainingPrincipalAt($loan, $schedule, $monthsPassed);
        $nextMonth = $monthsPassed + 1;
        $maxIndex = (int) ($schedule->max('month_index') ?? $loan->term_months ?? 1);
        $monthIndex = min(max(1, $nextMonth), max(1, $maxIndex));
        $period = $schedule->firstWhere('month_index', $monthIndex);
        $interest = (float) ($period['interest'] ?? 0);
        $year = SettlementFeeSchedule::yearFromMonthIndex($monthIndex);
        $fee = $fees->feeForYear($year, $remaining);

        return [
            'principal_remaining' => $remaining,
            'settlement_interest' => $interest,
            'settlement_fee' => $fee,
            'settlement_total' => $remaining + $interest + $fee,
            'year' => $year,
            'month_index' => $monthIndex,
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function buildSettlementRows(
        Loan $loan,
        Collection $schedule,
        int $monthsPassed,
        SettlementFeeSchedule $fees,
        float $collected,
    ): array {
        $rows = [];

        foreach ($schedule as $row) {
            $monthIndex = (int) $row['month_index'];
            $principalBefore = $this->paymentSchedule->principalBeforePeriod($schedule, $monthIndex);
            $interest = (float) ($row['interest'] ?? 0);
            $year = SettlementFeeSchedule::yearFromMonthIndex($monthIndex);
            $settlementFee = $fees->feeForYear($year, $principalBefore);
            $settlementTotal = $principalBefore + $interest + $settlementFee;
            $progress = $settlementTotal > 0
                ? min(100.0, round(($collected / $settlementTotal) * 100, 1))
                : 0.0;
            $due = $this->paymentSchedule->periodDueDate($loan, $row);

            $rows[] = [
                'month_index' => $monthIndex,
                'due_date' => $due->toDateString(),
                'year' => $year,
                'payment' => (float) ($row['payment'] ?? 0),
                'principal' => (float) ($row['principal'] ?? 0),
                'interest' => $interest,
                'fee' => (float) ($row['fee'] ?? 0),
                'days' => isset($row['days']) ? (int) $row['days'] : null,
                'principal_before' => $principalBefore,
                'remaining_principal' => (float) ($row['remaining_principal'] ?? 0),
                'settlement_interest' => $interest,
                'settlement_fee' => $settlementFee,
                'settlement_total' => $settlementTotal,
                'progress_percent' => $progress,
                'can_settle' => $collected >= $settlementTotal && $settlementTotal > 0,
                'is_paid' => $monthIndex <= $monthsPassed,
                'is_current' => $monthIndex === ($monthsPassed + 1),
            ];
        }

        return $rows;
    }

    /**
     * @return array{schedule: Collection, months_passed: int}|null
     */
    private function buildLoanSchedule(Loan $loan): ?array
    {
        if ($loan->type !== 'bank') {
            return null;
        }

        $this->scheduleGenerator->backfillPastPeriods($loan);
        $loan->refresh()->load(['payments', 'wallet', 'customSchedules']);

        $schedule = $this->amortization->calculate(
            $loan->id,
            $loan->principal_amount,
            $loan->interest_rate,
            $loan->term_months,
            $loan->started_at,
            $loan->monthly_payment,
            $loan->interest_calculation_method ?? 'monthly',
            $loan->payment_day,
            $loan->collection_fee ?? 0,
        );

        if ($schedule->isEmpty()) {
            return null;
        }

        $monthsPassed = $this->paymentSchedule->effectiveMonthsPaid($loan, $schedule, $loan->payments);

        return [
            'schedule' => $schedule,
            'months_passed' => $monthsPassed,
        ];
    }
}
