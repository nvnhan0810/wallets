<?php

namespace Wallets\RecurringPlanning\Application\Handler;

use App\Models\RecurringItem;
use App\Models\Setting;
use App\Models\Wallet;
use Carbon\Carbon;
use Wallets\RecurringPlanning\Application\Command\CreateRecurringItem;
use Wallets\RecurringPlanning\Application\RecurringOccurrenceGenerator;
use Wallets\Shared\Application\Command;
use Wallets\Shared\Application\CommandHandler;

final class CreateRecurringItemHandler implements CommandHandler
{
    public function __construct(private readonly RecurringOccurrenceGenerator $generator) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof CreateRecurringItem);
        Wallet::query()->forUser($command->userId)->findOrFail($command->data['wallet_id']);

        $item = RecurringItem::create(['user_id' => $command->userId, ...$command->data]);

        $horizon = Carbon::today()->addDays(Setting::recurringAlertDays($command->userId));
        $this->generator->ensure($item, $horizon);

        return $item;
    }
}
