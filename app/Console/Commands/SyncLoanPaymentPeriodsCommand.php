<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Services\LoanPaymentScheduleService;
use Illuminate\Console\Command;

class SyncLoanPaymentPeriodsCommand extends Command
{
    protected $signature = 'loans:sync-payment-periods';

    protected $description = 'Đồng bộ chi cố định với khoản vay ngân hàng và kiểm tra kỳ thanh toán';

    public function handle(LoanPaymentScheduleService $scheduleService): int
    {
        $loans = Loan::query()
            ->where('type', 'bank')
            ->where('is_settled', false)
            ->get();

        $synced = 0;
        foreach ($loans as $loan) {
            $scheduleService->syncRecurringItem($loan);
            $synced++;
        }

        $this->info("Đã đồng bộ {$synced} khoản vay ngân hàng.");

        return self::SUCCESS;
    }
}
