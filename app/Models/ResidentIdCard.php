<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentIdCard extends Model
{
    protected $fillable = [
        'resident_profile_id',
        'label',
        'file_path',
    ];

    public function profile()
    {
        return $this->belongsTo(ResidentProfile::class, 'resident_profile_id');
    }
}
