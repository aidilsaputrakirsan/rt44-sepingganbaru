<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterNumber extends Model
{
    protected $fillable = [
        'nomor_urut',
        'tahun',
        'bulan',
        'jenis',
        'keterangan',
        'payload',
        'tanggal',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'payload' => 'array',
    ];

    private const ROMAN = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Bulan dalam angka Romawi. */
    public static function roman(int $bulan): string
    {
        return self::ROMAN[$bulan] ?? '';
    }

    /** Susun nomor surat: 001/RT.44/VI/2026 */
    public static function format(int $nomorUrut, int $bulan, int $tahun): string
    {
        return sprintf('%03d/RT.44/%s/%d', $nomorUrut, self::roman($bulan), $tahun);
    }

    /** Nomor surat terformat dari atribut model. */
    public function getNomorFormatAttribute(): string
    {
        return self::format($this->nomor_urut, $this->bulan, $this->tahun);
    }

    /** Nomor urut berikutnya untuk satu tahun (reset tiap tahun). */
    public static function nextNomorUrut(int $tahun): int
    {
        return (int) self::where('tahun', $tahun)->max('nomor_urut') + 1;
    }
}
