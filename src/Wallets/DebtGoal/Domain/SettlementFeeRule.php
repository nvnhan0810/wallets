<?php

namespace Wallets\DebtGoal\Domain;

use DomainException;

final class SettlementFeeRule
{
    public function __construct(
        public readonly int $year,
        public readonly string $type,
        public readonly float $value,
    ) {
        if ($year < 1) {
            throw new DomainException('Năm phí tất toán phải từ 1 trở lên.');
        }
        if (! SettlementFeeType::isValid($type)) {
            throw new DomainException('Loại phí tất toán không hợp lệ.');
        }
        if ($value < 0) {
            throw new DomainException('Giá trị phí tất toán không được âm.');
        }
    }

    public function feeFor(float $remainingPrincipal): float
    {
        if ($this->type === SettlementFeeType::FIXED) {
            return round($this->value, 0);
        }

        return round($remainingPrincipal * ($this->value / 100), 0);
    }

    /** @return array{year:int,type:string,value:float} */
    public function toArray(): array
    {
        return [
            'year' => $this->year,
            'type' => $this->type,
            'value' => $this->value,
        ];
    }
}
