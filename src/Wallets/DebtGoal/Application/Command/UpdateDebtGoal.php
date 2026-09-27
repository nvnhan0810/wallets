<?php

namespace Wallets\DebtGoal\Application\Command;

use Wallets\Shared\Application\Command;

final class UpdateDebtGoal implements Command
{
    /**
     * @param  list<array{year:int,type:string,value:float|int|string}>  $settlementFees
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $name,
        public readonly int $walletId,
        public readonly ?float $targetAmount = null,
        public readonly ?int $loanId = null,
        public readonly array $settlementFees = [],
        public readonly bool $clear = false,
    ) {}
}
