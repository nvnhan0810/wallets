<?php

namespace Wallets\DebtGoal\Application\Handler;

use Wallets\DebtGoal\Application\DebtGoalProjectionService;
use Wallets\DebtGoal\Application\Query\GetDebtGoalProgress;
use Wallets\Shared\Application\Query;
use Wallets\Shared\Application\QueryHandler;

final class GetDebtGoalProgressHandler implements QueryHandler
{
    public function __construct(private readonly DebtGoalProjectionService $projection) {}

    public function handle(Query $query): mixed
    {
        assert($query instanceof GetDebtGoalProgress);

        return $this->projection->resolveProgress($query->userId);
    }
}
