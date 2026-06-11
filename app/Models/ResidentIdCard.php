<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentIdCard extends Model
{
    protected $fillable = [
        'resident_profile_id',
        'label',
        'nama',
        'nomor_ktp',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'status_perkawinan',
        'agama',
        'pekerjaan',
        'golongan_darah',
        'kewarganegaraan',
        'file_path',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function profile()
    {
        return $this->belongsTo(ResidentProfile::class, 'resident_profile_id');
    }
}
