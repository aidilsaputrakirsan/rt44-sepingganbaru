<?php

namespace App\Services;

/**
 * Penilaian status gizi balita berdasarkan WHO Child Growth Standards (metode LMS).
 *
 * Z-score = ((nilai/M)^L - 1) / (L * S)   (jika L != 0)
 *         = ln(nilai/M) / S                (jika L == 0)
 *
 * Indikator:
 *  - TB/U (haz)  -> Stunting        (panjang/tinggi menurut umur)
 *  - BB/U (waz)  -> Underweight     (berat menurut umur)
 *  - BB/TB (wfl/wfh) -> Wasting     (berat menurut panjang/tinggi)
 *
 * Berlaku untuk balita 0-60 bulan. Di luar itu return null (bukan sasaran).
 */
class StuntingService
{
    public const USIA_MAKS_BULAN = 60;

    private array $std;

    public function __construct()
    {
        $this->std = require app_path('Support/who_lms.php');
    }

    /**
     * Hitung seluruh indikator dari satu pengukuran.
     *
     * @param string $jk 'L' | 'P'
     * @param int $umurBulan umur saat diukur (bulan)
     * @param float $beratKg
     * @param float $tinggiCm
     * @param string $caraUkur 'berdiri' | 'terlentang'
     * @return array{haz:?array, waz:?array, wfl:?array}
     */
    public function nilai(string $jk, int $umurBulan, float $beratKg, float $tinggiCm, string $caraUkur = 'berdiri'): array
    {
        $jk = $jk === 'P' ? 'P' : 'L';

        // Koreksi tinggi vs panjang (WHO): < 24 bln idealnya terlentang (panjang),
        // >= 24 bln berdiri (tinggi). Konversi selisih 0.7 cm bila cara ukur tak sesuai umur.
        $tinggiAdj = $tinggiCm;
        if ($umurBulan < 24 && $caraUkur === 'berdiri') {
            $tinggiAdj = $tinggiCm + 0.7; // diukur berdiri, dikonversi ke panjang
        } elseif ($umurBulan >= 24 && $caraUkur === 'terlentang') {
            $tinggiAdj = $tinggiCm - 0.7; // diukur terlentang, dikonversi ke tinggi
        }

        return [
            'haz' => $this->zUsia('haz', $jk, $umurBulan, $tinggiAdj, 'tbu'),
            'waz' => $this->zUsia('waz', $jk, $umurBulan, $beratKg, 'bbu'),
            'wfl' => $this->zTinggi($jk, $umurBulan, $beratKg, $tinggiAdj),
        ];
    }

    /** Indikator berbasis umur (TB/U, BB/U). */
    private function zUsia(string $key, string $jk, int $umurBulan, float $nilai, string $kategoriSet): ?array
    {
        if ($umurBulan < 0 || $umurBulan > self::USIA_MAKS_BULAN) return null;
        $lms = $this->std[$key][$jk][$umurBulan] ?? null;
        if (!$lms || $nilai <= 0) return null;

        $z = $this->lms($nilai, $lms);
        return $this->bungkus($z, $kategoriSet);
    }

    /** BB/TB: pilih tabel wfl (umur <24, kunci panjang) atau wfh (>=24, kunci tinggi). */
    private function zTinggi(string $jk, int $umurBulan, float $beratKg, float $tinggiCm): ?array
    {
        $set = $umurBulan < 24 ? 'wfl' : 'wfh';
        $table = $this->std[$set][$jk] ?? null;
        if (!$table || $beratKg <= 0) return null;

        // Bulatkan tinggi ke resolusi 0.1 cm terdekat yang tersedia di tabel.
        $key = number_format(round($tinggiCm * 10) / 10, 1, '.', '');
        $lms = $table[$key] ?? null;
        if (!$lms) {
            // di luar rentang tabel (mis. tinggi < 45 atau > 120 cm)
            return null;
        }
        $z = $this->lms($beratKg, $lms);
        return $this->bungkus($z, 'bbtb');
    }

    private function lms(float $nilai, array $lms): float
    {
        [$L, $M, $S] = $lms;
        if (abs($L) < 1e-9) {
            return log($nilai / $M) / $S;
        }
        return (pow($nilai / $M, $L) - 1) / ($L * $S);
    }

    private function bungkus(float $z, string $kategoriSet): array
    {
        return [
            'z'        => round($z, 2),
            'kategori' => $this->kategori($z, $kategoriSet),
        ];
    }

    /**
     * Klasifikasi sesuai ambang WHO / Permenkes No.2/2020.
     * Mengembalikan ['label', 'level'] — level: ok | warn | bad | severe | high.
     */
    private function kategori(float $z, string $set): array
    {
        switch ($set) {
            case 'tbu': // Stunting (TB/U)
                if ($z < -3) return ['label' => 'Sangat Pendek', 'level' => 'severe'];
                if ($z < -2) return ['label' => 'Pendek',        'level' => 'bad'];
                if ($z <= 3) return ['label' => 'Normal',        'level' => 'ok'];
                return ['label' => 'Tinggi', 'level' => 'high'];

            case 'bbu': // Berat menurut umur
                if ($z < -3) return ['label' => 'BB Sangat Kurang', 'level' => 'severe'];
                if ($z < -2) return ['label' => 'BB Kurang',        'level' => 'bad'];
                if ($z <= 1) return ['label' => 'Normal',           'level' => 'ok'];
                return ['label' => 'Risiko BB Lebih', 'level' => 'warn'];

            case 'bbtb': // Wasting (BB/TB)
                if ($z < -3) return ['label' => 'Gizi Buruk',  'level' => 'severe'];
                if ($z < -2) return ['label' => 'Gizi Kurang', 'level' => 'bad'];
                if ($z <= 1) return ['label' => 'Gizi Baik',   'level' => 'ok'];
                if ($z <= 2) return ['label' => 'Risiko Gizi Lebih', 'level' => 'warn'];
                if ($z <= 3) return ['label' => 'Gizi Lebih',  'level' => 'warn'];
                return ['label' => 'Obesitas', 'level' => 'high'];
        }
        return ['label' => '-', 'level' => 'ok'];
    }
}
