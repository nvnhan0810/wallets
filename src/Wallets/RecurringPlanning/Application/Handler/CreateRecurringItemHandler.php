<?php

namespace Wallets\RecurringPlanning\Application\Handler;

use App\Models\RecurringItem;
use App\Models\Wallet;
use Wallets\RecurringPlanning\Application\Command\CreateRecurringItem;
use Wallets\Shared\Application\Command;
use Wallets\Shared\Application\CommandHandler;

final class CreateRecurringItemHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        assert($command instanceof CreateRecurringItem);
        Wallet::query()->forUser($command->userId)->findOrFail($command->data['wallet_id']);

        return RecurringItem::create(['user_id' => $command->userId, ...$command->data]);
    }
}
