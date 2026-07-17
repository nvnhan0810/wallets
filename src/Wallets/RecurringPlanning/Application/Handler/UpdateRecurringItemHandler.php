<?php

namespace Wallets\RecurringPlanning\Application\Handler;

use App\Models\RecurringItem;
use App\Models\RecurringOccurrence;
use App\Models\Setting;
use App\Models\Wallet;
use Carbon\Carbon;
use Wallets\RecurringPlanning\Application\Command\UpdateRecurringItem;
use Wallets\RecurringPlanning\Application\RecurringOccurrenceGenerator;
use Wallets\Shared\Application\Command;
use Wallets\Shared\Application\CommandHandler;

final class UpdateRecurringItemHandler implements CommandHandler
{
    public function __construct(private readonly RecurringOccurrenceGenerator $generator) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof UpdateRecurringItem);
        Wallet::query()->forUser($command->userId)->findOrFail($command->data['wallet_id']);
        $item = RecurringItem::query()->forUser($command->userId)->findOrFail($command->recurringItemId);
        $item->update($command->data);

        $item->occurrences()
            ->whereIn('status', RecurringOccurrence::openStatuses())
            ->update(['expected_amount' => $item->amount]);

        $horizon = Carbon::today()->addDays(Setting::recurringAlertDays($command->userId));
        $this->generator->ensure($item->fresh(), $horizon);

        return $item;
    }
}
