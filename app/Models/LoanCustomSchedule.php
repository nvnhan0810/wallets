<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanCustomSchedule extends Model
{
    protected $fillable = [
        'loan_id',
        'month_index',
        'payment',
        'principal',
        'interest',
        'fee',
        'remaining_principal',
        'paid_at',
        'note',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'payment' => 'decimal:2',
        'principal' => 'decimal:2',
        'interest' => 'decimal:2',
        'fee' => 'decimal:2',
        'remaining_principal' => 'decimal:2',
    ];
}

