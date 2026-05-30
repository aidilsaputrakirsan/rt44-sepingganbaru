<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentIdCard extends Model
{
    protected $fillable = [
        'resident_profile_id',
        'label',
        'nomor_ktp',
        'file_path',
    ];

    public function profile()
    {
        return $this->belongsTo(ResidentProfile::class, 'resident_profile_id');
    }
}
