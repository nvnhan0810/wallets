<?php

namespace Wallets\Lending\Application\Command;

use Wallets\Shared\Application\Command;

final class UpdateLoan implements Command
{
    /**
     * @param  array{name: string, wallet_id: int|null}  $data
     */
    public function __construct(
        public readonly int $userId,
        public readonly int $loanId,
        public readonly array $data,
    ) {}
}
