<?php

namespace Wallets\DebtGoal\Domain;

final class DebtGoalProgress
{
    public function __construct(
        public readonly string $name,
        public readonly float $targetAmount,
        public readonly float $collected,
        public readonly float $percent,
        public readonly float $remaining,
        public readonly int $walletId,
        public readonly string $walletName,
        public readonly bool $reached,
        public readonly ?int $loanId = null,
        public readonly ?string $loanName = null,
        public readonly float $principalRemaining = 0.0,
        public readonly float $settlementInterest = 0.0,
        public readonly float $settlementFee = 0.0,
        public readonly int $currentYear = 0,
        public readonly bool $hasLoan = false,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'target_amount' => $this->targetAmount,
            'collected' => $this->collected,
            'percent' => $this->percent,
            'remaining' => $this->remaining,
            'wallet_id' => $this->walletId,
            'wallet_name' => $this->walletName,
            'reached' => $this->reached,
            'loan_id' => $this->loanId,
            'loan_name' => $this->loanName,
            'principal_remaining' => $this->principalRemaining,
            'settlement_interest' => $this->settlementInterest,
            'settlement_fee' => $this->settlementFee,
            'current_year' => $this->currentYear,
            'has_loan' => $this->hasLoan,
        ];
    }

    public static function make(
        string $name,
        float $targetAmount,
        float $walletBalance,
        int $walletId,
        string $walletName,
        ?int $loanId = null,
        ?string $loanName = null,
        float $principalRemaining = 0.0,
        float $settlementInterest = 0.0,
        float $settlementFee = 0.0,
        int $currentYear = 0,
    ): self {
        $collected = max(0.0, $walletBalance);
        $percent = $targetAmount > 0
            ? min(100.0, round(($collected / $targetAmount) * 100, 1))
            : 0.0;
        $remaining = max(0.0, $targetAmount - $collected);
        $hasLoan = $loanId !== null && $loanId > 0;

        return new self(
            name: $name,
            targetAmount: $targetAmount,
            collected: $collected,
            percent: $percent,
            remaining: $remaining,
            walletId: $walletId,
            walletName: $walletName,
            reached: $collected >= $targetAmount && $targetAmount > 0,
            loanId: $hasLoan ? $loanId : null,
            loanName: $hasLoan ? $loanName : null,
            principalRemaining: $principalRemaining,
            settlementInterest: $settlementInterest,
            settlementFee: $settlementFee,
            currentYear: $currentYear,
            hasLoan: $hasLoan,
        );
    }
}
