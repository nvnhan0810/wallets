<?php

namespace Wallets\Lending\Application;

use App\Models\Loan;
use App\Models\LoanCustomSchedule;
use Carbon\Carbon;
use Wallets\Lending\Domain\AmortizationCalculator;

/**
 * Vật chất hóa lịch kỳ trả vào bảng loan_custom_schedules cho khoản vay ngân hàng.
 * Kỳ đến hạn <= hôm nay (khi tạo hồi tố) được tự đánh dấu đã trả (không sinh giao dịch ví).
 */
class LoanScheduleGenerator
{
    public function __construct(
        private readonly AmortizationCalculator $amortization,
        private readonly LoanPaymentScheduleService $paymentSchedule,
    ) {}

    public function generate(Loan $loan, bool $backfillPast = true): void
    {
        if ($loan->type !== 'bank') {
            return;
        }

        $schedule = $this->amortization->calculate(
            $loan->id,
            $loan->principal_amount,
            $loan->interest_rate,
            $loan->term_months,
            $loan->started_at,
            $loan->monthly_payment,
            $loan->interest_calculation_method ?? 'monthly',
        );

        if ($schedule->isEmpty()) {
            return;
        }

        $today = Carbon::today()->startOfDay();
        $paidCount = 0;

        foreach ($schedule as $row) {
            $due = $this->paymentSchedule->periodDueDate($loan, $row);
            $isPast = $backfillPast && $due->lte($today);

            LoanCustomSchedule::updateOrCreate(
                ['loan_id' => $loan->id, 'month_index' => (int) $row['month_index']],
                [
                    'user_id' => $loan->user_id,
                    'due_date' => $due->toDateString(),
                    'payment' => $row['payment'],
                    'principal' => $row['principal'],
                    'interest' => $row['interest'],
                    'fee' => $row['fee'] ?? 0,
                    'remaining_principal' => $row['remaining_principal'],
                    'status' => $isPast ? LoanCustomSchedule::STATUS_PAID : LoanCustomSchedule::STATUS_PENDING,
                    'paid_amount' => $isPast ? $row['payment'] : null,
                    'paid_at' => $isPast ? $due->toDateString() : null,
                ]
            );

            if ($isPast) {
                $paidCount++;
            }
        }

        if ($paidCount > 0) {
            $loan->update(['months_paid' => max((int) $loan->months_paid, $paidCount)]);
        }
    }
}
