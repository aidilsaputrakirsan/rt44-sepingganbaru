<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleCount extends Model
{
    protected $fillable = [
        'house_id',
        'jumlah_mobil',
        'jumlah_motor',
    ];

    public function house()
    {
        return $this->belongsTo(House::class);
    }
}
