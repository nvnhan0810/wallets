<?php

namespace Wallets\Calendar\Application\Handler;

use App\Models\Holiday;
use Wallets\Calendar\Application\Command\DeleteHoliday;
use Wallets\Shared\Application\Command;
use Wallets\Shared\Application\CommandHandler;

final class DeleteHolidayHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        assert($command instanceof DeleteHoliday);
        Holiday::query()->findOrFail($command->holidayId)->delete();

        return null;
    }
}
