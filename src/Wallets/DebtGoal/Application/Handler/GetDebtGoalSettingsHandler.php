<?php

namespace Wallets\DebtGoal\Application\Handler;

use App\Models\Loan;
use App\Models\Setting;
use App\Models\Wallet;
use Wallets\DebtGoal\Application\DebtGoalProjectionService;
use Wallets\DebtGoal\Application\Query\GetDebtGoalProgress;
use Wallets\DebtGoal\Application\Query\GetDebtGoalSettings;
use Wallets\DebtGoal\Domain\DebtGoalSettingKey;
use Wallets\DebtGoal\Domain\SettlementFeeType;
use Wallets\Shared\Application\Query;
use Wallets\Shared\Application\QueryBus;
use Wallets\Shared\Application\QueryHandler;

final class GetDebtGoalSettingsHandler implements QueryHandler
{
    public function __construct(
        private readonly QueryBus $queries,
        private readonly DebtGoalProjectionService $projection,
    ) {}

    public function handle(Query $query): mixed
    {
        assert($query instanceof GetDebtGoalSettings);

        $wallets = Wallet::query()
            ->forUser($query->userId)
            ->where('is_active', true)
            ->where('type', '!=', 'credit_card')
            ->orderByDesc('is_pinned')
            ->orderBy('order')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'balance'])
            ->map(static fn (Wallet $wallet): array => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'type' => $wallet->type,
                'type_label' => $wallet->typeLabel(),
                'balance' => (float) $wallet->balance,
            ])
            ->values()
            ->all();

        $loans = Loan::query()
            ->forUser($query->userId)
            ->where('is_settled', false)
            ->where('type', 'bank')
            ->orderBy('name')
            ->get(['id', 'name', 'principal_amount', 'term_months', 'started_at'])
            ->map(static fn (Loan $loan): array => [
                'id' => $loan->id,
                'name' => $loan->name,
                'principal_amount' => (float) $loan->principal_amount,
                'term_months' => (int) ($loan->term_months ?? 0),
                'started_at' => optional($loan->started_at)?->toDateString(),
            ])
            ->values()
            ->all();

        $name = (string) Setting::getForUser($query->userId, DebtGoalSettingKey::NAME, '');
        $targetAmount = Setting::getForUser($query->userId, DebtGoalSettingKey::TARGET_AMOUNT);
        $walletId = Setting::getForUser($query->userId, DebtGoalSettingKey::WALLET_ID);
        $loanId = Setting::getForUser($query->userId, DebtGoalSettingKey::LOAN_ID);
        $fees = $this->projection->feeScheduleForUser($query->userId);

        $progress = $this->queries->ask(new GetDebtGoalProgress(userId: $query->userId));

        return [
            'goal' => [
                'name' => $name !== '' ? $name : 'Mục tiêu trả nợ',
                'target_amount' => $targetAmount !== null && $targetAmount !== '' ? (float) $targetAmount : null,
                'wallet_id' => $walletId !== null && $walletId !== '' ? (int) $walletId : null,
                'loan_id' => $loanId !== null && $loanId !== '' ? (int) $loanId : null,
                'settlement_fees' => $fees->toArray(),
            ],
            'wallets' => $wallets,
            'loans' => $loans,
            'fee_types' => [
                ['value' => SettlementFeeType::PERCENT, 'label' => '% dư nợ'],
                ['value' => SettlementFeeType::FIXED, 'label' => 'Số tiền cố định'],
            ],
            'progress' => $progress,
        ];
    }
}
