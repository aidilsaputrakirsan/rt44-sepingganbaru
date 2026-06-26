<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyReportActivity extends Model
{
    protected $fillable = [
        'monthly_report_id',
        'tanggal',
        'uraian',
        'no_surat',
        'photo_path',
        'photo_orientation',
        'sort_order',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function report()
    {
        return $this->belongsTo(MonthlyReport::class, 'monthly_report_id');
    }
}
