<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyBalance extends Model
{
    protected $fillable = [
        'period',
        'initial_balance',
        'notes',
    ];

    protected $casts = [
        'period' => 'date',
        'initial_balance' => 'decimal:2',
    ];
}
