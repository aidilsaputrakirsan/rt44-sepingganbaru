<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    /** Nilai sah untuk kolom blok_group. */
    public const BLOK_GROUPS = ['G', 'H', 'shared'];

    protected $fillable = [
        'title',
        'amount',
        'category',
        'blok_group',
        'date',
        'proof_path',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
