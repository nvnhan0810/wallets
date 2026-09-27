<?php

namespace Wallets\DebtGoal\Application\Handler;

use App\Models\Setting;
use Wallets\DebtGoal\Application\DebtGoalProjectionService;
use Wallets\DebtGoal\Application\Query\GetDebtGoalDetail;
use Wallets\DebtGoal\Domain\DebtGoalSettingKey;
use Wallets\Shared\Application\Query;
use Wallets\Shared\Application\QueryHandler;

final class GetDebtGoalDetailHandler implements QueryHandler
{
    public function __construct(private readonly DebtGoalProjectionService $projection) {}

    public function handle(Query $query): mixed
    {
        assert($query instanceof GetDebtGoalDetail);

        $detail = $this->projection->resolveDetail($query->userId);
        if ($detail === null) {
            return null;
        }

        $loan = $detail['loan'];
        $name = (string) Setting::getForUser($query->userId, DebtGoalSettingKey::NAME, 'Mục tiêu trả nợ');
        if (trim($name) === '') {
            $name = 'Mục tiêu trả nợ';
        }

        $wallet = $detail['wallet'];
        $current = $detail['current'];
        $collected = $detail['collected'];
        $target = $current['settlement_total'] ?? 0.0;
        $percent = $target > 0 ? min(100.0, round(($collected / $target) * 100, 1)) : 0.0;

        return [
            'goal_name' => $name,
            'loan' => [
                'id' => $loan->id,
                'name' => $loan->name,
                'type' => $loan->type,
                'principal_amount' => (float) $loan->principal_amount,
                'interest_rate' => (float) $loan->interest_rate,
                'interest_calculation_method' => $loan->interest_calculation_method ?? 'monthly',
                'term_months' => (int) ($loan->term_months ?? 0),
                'started_at' => optional($loan->started_at)?->toDateString(),
                'monthly_payment' => (float) ($loan->monthly_payment ?? 0),
                'collection_fee' => (float) ($loan->collection_fee ?? 0),
            ],
            'wallet' => $wallet ? [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'balance' => (float) $wallet->balance,
            ] : null,
            'months_passed' => $detail['months_passed'],
            'payment_day' => $detail['payment_day'],
            'current' => $current,
            'collected' => $collected,
            'percent' => $percent,
            'rows' => $detail['rows'],
            'fees' => $detail['fees'],
        ];
    }
}
