<?php

namespace Wallets\Lending\Application;

use App\Models\LoanCustomSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;
use Wallets\Lending\Application\Command\RecordLoanPayment;
use Wallets\Shared\Application\CommandBus;

/**
 * Cuối ngày: kỳ đến hạn chưa có payment/transaction → tạo thanh toán từ ví gắn trên khoản vay.
 */
class LoanPeriodAutoPayService
{
    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public function __construct(
        private readonly CommandBus $commands,
    ) {}

    public function payDuePeriodsFromLinkedWallets(?Carbon $asOf = null): int
    {
        $today = ($asOf ?? Carbon::now(self::TIMEZONE))->timezone(self::TIMEZONE)->toDateString();

        $periods = LoanCustomSchedule::query()
            ->with(['loan', 'payment'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $today)
            ->where('status', '!=', LoanCustomSchedule::STATUS_PAID)
            ->whereNull('payment_id')
            ->whereHas('loan', function ($query): void {
                $query
                    ->whereNotNull('wallet_id')
                    ->where('is_settled', false);
            })
            ->orderBy('due_date')
            ->orderBy('month_index')
            ->get();

        $paid = 0;
        $skipped = 0;

        foreach ($periods as $period) {
            if ($this->alreadySettled($period)) {
                $skipped++;

                continue;
            }

            $loan = $period->loan;
            if ($loan === null || $loan->wallet_id === null) {
                $skipped++;

                continue;
            }

            $amount = (float) $period->payment;
            if ($amount <= 0) {
                $skipped++;

                continue;
            }

            try {
                $this->commands->dispatch(new RecordLoanPayment(
                    userId: (int) $loan->user_id,
                    data: [
                        'loan_id' => $loan->id,
                        'wallet_id' => $loan->wallet_id,
                        'amount' => $amount,
                        'paid_at' => $period->due_date->toDateString(),
                        'note' => 'Thanh toán tự động theo lịch (kỳ '.$period->month_index.')',
                        'period_id' => $period->id,
                    ],
                ));
                $paid++;
            } catch (Throwable $e) {
                Log::warning('loan.auto_pay_failed', [
                    'loan_id' => $loan->id,
                    'period_id' => $period->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        Log::info('loan.auto_pay_finished', [
            'as_of' => $today,
            'paid' => $paid,
            'skipped' => $skipped,
        ]);

        return $paid;
    }

    private function alreadySettled(LoanCustomSchedule $period): bool
    {
        if ($period->status === LoanCustomSchedule::STATUS_PAID) {
            return true;
        }

        if ($period->payment_id !== null) {
            return true;
        }

        $payment = $period->payment;
        if ($payment !== null && $payment->transaction_id !== null) {
            return true;
        }

        return false;
    }
}
