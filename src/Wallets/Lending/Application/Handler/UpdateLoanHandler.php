<?php

namespace Wallets\Lending\Application\Handler;

use App\Models\Loan;
use App\Models\Wallet;
use Wallets\Lending\Application\Command\UpdateLoan;
use Wallets\Shared\Application\Command;
use Wallets\Shared\Application\CommandHandler;

final class UpdateLoanHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        assert($command instanceof UpdateLoan);

        $loan = Loan::query()->forUser($command->userId)->findOrFail($command->loanId);
        $walletId = $command->data['wallet_id'] ?? null;

        if ($walletId !== null) {
            Wallet::query()->forUser($command->userId)->findOrFail($walletId);
        }

        $loan->update([
            'name' => $command->data['name'],
            'wallet_id' => $walletId,
        ]);

        return $loan->fresh(['wallet']);
    }
}
