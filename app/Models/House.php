<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Due;

class House extends Model
{
    protected $fillable = [
        'blok',
        'nomor',
        'status_huni',
        'resident_status',
        'is_connected',
        'meteran_count',
        'owner_id',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function dues()
    {
        return $this->hasMany(Due::class);
    }
    //
}
