<?php

namespace App\Console\Commands;

use App\Models\Loan;
use Illuminate\Console\Command;
use Wallets\Lending\Application\LoanScheduleGenerator;
use Wallets\Lending\Application\LoanScheduleStateService;

class ProcessLoanPeriodsCommand extends Command
{
    protected $signature = 'loans:process-periods';

    protected $description = 'Backfill kỳ quá hạn (không tạo giao dịch) và cập nhật due/overdue — không gửi nhắc, không auto-pay';

    public function handle(
        LoanScheduleStateService $state,
        LoanScheduleGenerator $generator,
    ): int {
        foreach (Loan::query()->where('type', 'bank')->where('is_settled', false)->cursor() as $loan) {
            $generator->backfillPastPeriods($loan);
        }

        $state->transitionStatuses();
        $this->info('Đã cập nhật trạng thái kỳ vay.');

        return self::SUCCESS;
    }
}
