<?php

namespace Wallets\DebtGoal\Domain;

use DomainException;

final class SettlementFeeSchedule
{
    /** @param list<SettlementFeeRule> $rules */
    public function __construct(private readonly array $rules)
    {
        $years = [];
        foreach ($this->rules as $rule) {
            if (isset($years[$rule->year])) {
                throw new DomainException('Trùng năm trong bảng phí tất toán.');
            }
            $years[$rule->year] = true;
        }
    }

    /**
     * @param  list<array{year?:mixed,type?:mixed,value?:mixed}>|null  $raw
     */
    public static function fromArray(?array $raw): self
    {
        if ($raw === null || $raw === []) {
            return new self([]);
        }

        $rules = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $year = (int) ($row['year'] ?? 0);
            $type = (string) ($row['type'] ?? SettlementFeeType::PERCENT);
            $value = (float) ($row['value'] ?? 0);
            if ($year < 1) {
                continue;
            }
            $rules[] = new SettlementFeeRule($year, $type, $value);
        }

        usort($rules, static fn (SettlementFeeRule $a, SettlementFeeRule $b): int => $a->year <=> $b->year);

        return new self($rules);
    }

    public static function fromJson(?string $json): self
    {
        if ($json === null || trim($json) === '') {
            return new self([]);
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return new self([]);
        }

        /** @var list<array{year?:mixed,type?:mixed,value?:mixed}> $decoded */
        return self::fromArray($decoded);
    }

    public static function yearFromMonthIndex(int $monthIndex): int
    {
        return max(1, (int) ceil($monthIndex / 12));
    }

    public function feeForYear(int $year, float $remainingPrincipal): float
    {
        $rule = $this->ruleForYear($year);
        if ($rule === null) {
            return 0.0;
        }

        return $rule->feeFor($remainingPrincipal);
    }

    public function ruleForYear(int $year): ?SettlementFeeRule
    {
        $exact = null;
        $fallback = null;
        foreach ($this->rules as $rule) {
            if ($rule->year === $year) {
                $exact = $rule;
                break;
            }
            if ($rule->year < $year && ($fallback === null || $rule->year > $fallback->year)) {
                $fallback = $rule;
            }
        }

        return $exact ?? $fallback;
    }

    /** @return list<array{year:int,type:string,value:float}> */
    public function toArray(): array
    {
        return array_map(static fn (SettlementFeeRule $rule): array => $rule->toArray(), $this->rules);
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /** @return list<SettlementFeeRule> */
    public function rules(): array
    {
        return $this->rules;
    }
}
