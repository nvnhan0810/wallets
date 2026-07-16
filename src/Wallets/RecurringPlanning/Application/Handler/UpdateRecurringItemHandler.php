<?php

namespace Wallets\RecurringPlanning\Application\Handler;

use App\Models\RecurringItem;
use App\Models\Wallet;
use Wallets\RecurringPlanning\Application\Command\UpdateRecurringItem;
use Wallets\Shared\Application\Command;
use Wallets\Shared\Application\CommandHandler;

final class UpdateRecurringItemHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        assert($command instanceof UpdateRecurringItem);
        Wallet::query()->forUser($command->userId)->findOrFail($command->data['wallet_id']);
        $item = RecurringItem::query()->forUser($command->userId)->findOrFail($command->recurringItemId);
        $item->update($command->data);

        return $item;
    }
}
