<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    protected $fillable = [
        'type',
        'name',
        'principal_amount',
        'interest_rate',
        'interest_calculation_method',
        'term_months',
        'months_paid',
        'monthly_payment',
        'started_at',
        'is_settled',
    ];

    protected $casts = [
        'started_at' => 'date',
        'is_settled' => 'boolean',
        'principal_amount' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}

