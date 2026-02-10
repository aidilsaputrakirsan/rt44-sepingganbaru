<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'title',
        'amount',
        'category',
        'date',
        'proof_path',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
