<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Wallets\Lending\Application\LoanPeriodAutoPayService;

class AutoPayDueLoanPeriodsCommand extends Command
{
    protected $signature = 'loans:auto-pay-due';

    protected $description = 'Cuối ngày: tạo payment + transaction cho kỳ vay đến hạn chưa trả, từ ví gắn trên khoản vay';

    public function handle(LoanPeriodAutoPayService $autoPay): int
    {
        $paid = $autoPay->payDuePeriodsFromLinkedWallets();
        $this->info("Đã tạo {$paid} thanh toán tự động từ ví gắn khoản vay.");

        return self::SUCCESS;
    }
}
