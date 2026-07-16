<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
                'type',
        'name',
        'principal_amount',
        'interest_rate',
        'interest_calculation_method',
        'term_months',
        'months_paid',
        'monthly_payment',
        'payment_day',
        'started_at',
        'is_settled',
        'wallet_id',
        'recurring_item_id',
    ];

    protected $casts = [
        'started_at' => 'date',
        'is_settled' => 'boolean',
        'principal_amount' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function customSchedules(): HasMany
    {
        return $this->hasMany(LoanCustomSchedule::class);
    }

    public function recurringItem(): BelongsTo
    {
        return $this->belongsTo(RecurringItem::class);
    }

    public function isBankLoan(): bool
    {
        return $this->type === 'bank';
    }
}

