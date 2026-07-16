<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Wallets\Lending\Application\Command\SyncLoanPaymentPeriods;
use Wallets\Shared\Application\CommandBus;

class SyncLoanPaymentPeriodsCommand extends Command
{
    protected $signature = 'loans:sync-payment-periods';

    protected $description = 'Đồng bộ chi cố định với khoản vay ngân hàng và kiểm tra kỳ thanh toán';

    public function handle(CommandBus $commands): int
    {
        $synced = $commands->dispatch(new SyncLoanPaymentPeriods);

        $this->info("Đã đồng bộ {$synced} khoản vay ngân hàng.");

        return self::SUCCESS;
    }
}
