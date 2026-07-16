<?php

namespace Wallets\Lending\Application\Handler;

use App\Models\Loan;
use App\Models\Payment;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Wallets\Lending\Application\Command\RecordLoanPayment;
use Wallets\Lending\Application\LoanPaymentScheduleService;
use Wallets\Lending\Application\LoanWalletService;
use Wallets\Lending\Domain\AmortizationCalculator;
use Wallets\Shared\Application\Command;
use Wallets\Shared\Application\CommandHandler;

final class RecordLoanPaymentHandler implements CommandHandler
{
    public function __construct(
        private readonly LoanWalletService $loanWallet,
        private readonly LoanPaymentScheduleService $paymentSchedule,
        private readonly AmortizationCalculator $amortization,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof RecordLoanPayment);

        $data = $command->data;
        $wasEarly = false;

        DB::transaction(function () use ($command, $data, &$wasEarly) {
            $loan = Loan::query()->forUser($command->userId)->findOrFail($data['loan_id']);
            $wallet = Wallet::query()->forUser($command->userId)->findOrFail($data['wallet_id']);
            $paidAt = Carbon::parse($data['paid_at']);

            $periodMeta = ['kind' => Payment::KIND_PERIOD, 'reduces_principal' => true];
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
                $resolved = $this->paymentSchedule->resolvePeriodForPayment($loan, $paidAt, $schedule);
                $periodMeta = [
                    'kind' => $resolved['kind'],
                    'period_due_date' => $resolved['period_due_date']->toDateString(),
                    'schedule_month_index' => $resolved['schedule_month_index'],
                    'reduces_principal' => $resolved['reduces_principal'],
                ];
            }

            $payment = Payment::create([
                'loan_id' => $data['loan_id'],
                'amount' => $data['amount'],
                'paid_at' => $data['paid_at'],
                'note' => $data['note'] ?? null,
                ...$periodMeta,
            ]);

            $this->loanWallet->recordPayment($loan, $payment, $wallet);

            if ($loan->type === 'bank' && $payment->reduces_principal && $payment->schedule_month_index) {
                $loan->update([
                    'months_paid' => max((int) $loan->months_paid, (int) $payment->schedule_month_index),
                ]);
            }

            if (! $loan->wallet_id) {
                $loan->update(['wallet_id' => $wallet->id]);
            }

            if ($loan->type === 'bank') {
                $this->paymentSchedule->syncRecurringItem($loan->fresh());
            }

            $wasEarly = $payment->isEarly();
        });

        return ['was_early' => $wasEarly];
    }
}
