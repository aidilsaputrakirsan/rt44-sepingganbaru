<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyReport extends Model
{
    protected $fillable = [
        'period',
        'tanggal_pengesahan',
        'lurah_name',
        'ketua_name',
        'created_by',
    ];

    protected $casts = [
        'period' => 'date',
        'tanggal_pengesahan' => 'date',
    ];

    public function activities()
    {
        // Selalu urut berdasarkan tanggal kegiatan (awal → akhir bulan),
        // lalu id sebagai tie-breaker untuk tanggal yang sama.
        return $this->hasMany(MonthlyReportActivity::class)->orderBy('tanggal')->orderBy('id');
    }
}
