<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    public const TYPES = [
        'cash' => 'Tiền mặt',
        'bank' => 'Tài khoản ngân hàng',
        'credit_card' => 'Thẻ tín dụng',
        'e_wallet' => 'Ví điện tử',
    ];

    protected $fillable = [
        'name',
        'type',
        'balance',
        'credit_limit',
        'statement_day',
        'payment_day',
        'outstanding_balance',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function recurringItems(): HasMany
    {
        return $this->hasMany(RecurringItem::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function isCreditCard(): bool
    {
        return $this->type === 'credit_card';
    }

    /** Số tiền còn có thể chi (thẻ: hạn mức − dư nợ; ví khác: số dư). */
    public function spendableBalance(): float
    {
        if ($this->isCreditCard()) {
            return max(0, (float) $this->credit_limit - (float) ($this->outstanding_balance ?? 0));
        }

        return (float) $this->balance;
    }

    public function syncCreditAvailableBalance(): void
    {
        if (! $this->isCreditCard()) {
            return;
        }

        $this->balance = max(0, (float) $this->credit_limit - (float) ($this->outstanding_balance ?? 0));
    }

    public function applyTransaction(string $type, float $amount): void
    {
        if ($this->isCreditCard()) {
            if ($type === 'expense') {
                $this->outstanding_balance = (float) ($this->outstanding_balance ?? 0) + $amount;
            } else {
                $this->outstanding_balance = max(0, (float) ($this->outstanding_balance ?? 0) - $amount);
            }
            $this->syncCreditAvailableBalance();
            $this->save();

            return;
        }

        if ($type === 'income') {
            $this->increment('balance', $amount);
        } else {
            $this->decrement('balance', $amount);
        }
    }

    public function reverseTransaction(string $type, float $amount): void
    {
        $this->applyTransaction($type === 'income' ? 'expense' : 'income', $amount);
    }
}
