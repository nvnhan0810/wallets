<?php

namespace Wallets\Lending\Application\Handler;

use App\Models\Loan;
use Wallets\Lending\Application\Command\SyncLoanPaymentPeriods;
use Wallets\Lending\Application\LoanPaymentScheduleService;
use Wallets\Shared\Application\Command;
use Wallets\Shared\Application\CommandHandler;

final class SyncLoanPaymentPeriodsHandler implements CommandHandler
{
    public function __construct(private readonly LoanPaymentScheduleService $paymentSchedule) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof SyncLoanPaymentPeriods);

        $query = Loan::query()
            ->where('is_settled', false)
            ->where('type', 'bank');

        if ($command->userId !== null) {
            $query->forUser($command->userId);
        }

        $count = 0;
        foreach ($query->get() as $loan) {
            $this->paymentSchedule->syncRecurringItem($loan);
            $count++;
        }

        return $count;
    }
}
