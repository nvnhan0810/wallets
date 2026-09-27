<?php

namespace Wallets\DebtGoal\Application\Handler;

use App\Models\Loan;
use App\Models\Setting;
use App\Models\Wallet;
use DomainException;
use Wallets\DebtGoal\Application\Command\UpdateDebtGoal;
use Wallets\DebtGoal\Domain\DebtGoalSettingKey;
use Wallets\DebtGoal\Domain\SettlementFeeSchedule;
use Wallets\Shared\Application\Command;
use Wallets\Shared\Application\CommandHandler;

final class UpdateDebtGoalHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        assert($command instanceof UpdateDebtGoal);

        if ($command->clear) {
            Setting::setForUser($command->userId, DebtGoalSettingKey::NAME, '');
            Setting::setForUser($command->userId, DebtGoalSettingKey::TARGET_AMOUNT, '');
            Setting::setForUser($command->userId, DebtGoalSettingKey::WALLET_ID, '');
            Setting::setForUser($command->userId, DebtGoalSettingKey::LOAN_ID, '');
            Setting::setForUser($command->userId, DebtGoalSettingKey::SETTLEMENT_FEES, '[]');

            return null;
        }

        $wallet = Wallet::query()
            ->forUser($command->userId)
            ->where('id', $command->walletId)
            ->where('is_active', true)
            ->where('type', '!=', 'credit_card')
            ->first();

        if ($wallet === null) {
            throw new DomainException('Ví không hợp lệ hoặc không thể dùng để gom tiền trả nợ.');
        }

        $loanId = $command->loanId;
        if ($loanId !== null && $loanId > 0) {
            $loan = Loan::query()
                ->forUser($command->userId)
                ->where('id', $loanId)
                ->where('is_settled', false)
                ->first();

            if ($loan === null) {
                throw new DomainException('Khoản vay không hợp lệ hoặc đã tất toán.');
            }
        } else {
            $loanId = null;
            if ($command->targetAmount === null || $command->targetAmount <= 0) {
                throw new DomainException('Số tiền mục tiêu phải lớn hơn 0 khi không gắn khoản vay.');
            }
        }

        $fees = SettlementFeeSchedule::fromArray($command->settlementFees);
        $name = trim($command->name) !== '' ? trim($command->name) : 'Mục tiêu trả nợ';

        Setting::setForUser($command->userId, DebtGoalSettingKey::NAME, $name);
        Setting::setForUser($command->userId, DebtGoalSettingKey::WALLET_ID, (string) $command->walletId);
        Setting::setForUser($command->userId, DebtGoalSettingKey::LOAN_ID, $loanId !== null ? (string) $loanId : '');
        Setting::setForUser($command->userId, DebtGoalSettingKey::SETTLEMENT_FEES, $fees->toJson());

        if ($loanId !== null) {
            Setting::setForUser($command->userId, DebtGoalSettingKey::TARGET_AMOUNT, '');
        } else {
            Setting::setForUser($command->userId, DebtGoalSettingKey::TARGET_AMOUNT, (string) $command->targetAmount);
        }

        return null;
    }
}
