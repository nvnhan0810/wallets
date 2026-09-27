<?php

namespace Wallets\DebtGoal\Application\Query;

use Wallets\Shared\Application\Query;

final class GetDebtGoalSettings implements Query
{
    public function __construct(public readonly int $userId) {}
}
