<?php

namespace Wallets\DebtGoal\Domain;

final class SettlementFeeType
{
    public const PERCENT = 'percent';

    public const FIXED = 'fixed';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::PERCENT, self::FIXED];
    }

    public static function isValid(string $type): bool
    {
        return in_array($type, self::all(), true);
    }

    private function __construct() {}
}
