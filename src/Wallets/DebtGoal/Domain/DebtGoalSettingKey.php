<?php

namespace Wallets\DebtGoal\Domain;

final class DebtGoalSettingKey
{
    public const NAME = 'debt_goal_name';

    public const TARGET_AMOUNT = 'debt_goal_target_amount';

    public const WALLET_ID = 'debt_goal_wallet_id';

    public const LOAN_ID = 'debt_goal_loan_id';

    public const SETTLEMENT_FEES = 'debt_goal_settlement_fees';

    private function __construct() {}
}
