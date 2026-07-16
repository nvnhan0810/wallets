<?php

namespace Wallets\Calendar\Application\Handler;

use App\Models\Holiday;
use Wallets\Calendar\Application\Command\CreateHoliday;
use Wallets\Shared\Application\Command;
use Wallets\Shared\Application\CommandHandler;

final class CreateHolidayHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        assert($command instanceof CreateHoliday);

        return Holiday::create($command->data);
    }
}
