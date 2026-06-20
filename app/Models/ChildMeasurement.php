<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChildMeasurement extends Model
{
    protected $fillable = [
        'resident_id_card_id',
        'tanggal_ukur',
        'berat_kg',
        'tinggi_cm',
        'lila_cm',
        'lingkar_kepala_cm',
        'vitamin_a',
        'asi_eksklusif',
        'pmt_ke',
        'pmt_sumber',
        'cara_ukur',
        'catatan',
        'recorded_by',
    ];

    protected $casts = [
        'tanggal_ukur'  => 'date',
        'berat_kg'      => 'decimal:2',
        'tinggi_cm'     => 'decimal:1',
        'lila_cm'       => 'decimal:1',
        'lingkar_kepala_cm' => 'decimal:1',
        'vitamin_a'     => 'boolean',
        'asi_eksklusif' => 'array',
    ];

    public function idCard()
    {
        return $this->belongsTo(ResidentIdCard::class, 'resident_id_card_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
