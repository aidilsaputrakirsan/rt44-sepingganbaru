<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Due extends Model
{
    protected $fillable = [
        'house_id',
        'period',
        'amount',
        'status',
        'due_date',
    ];

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
