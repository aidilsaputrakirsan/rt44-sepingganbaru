<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Ubah isian identitas mentah (hasil baca foto KTP/KK atau sel Excel) ke nilai baku form
 * Data Warga. Dipakai bersama oleh PembacaKtp, PembacaKk, dan import Excel anggota keluarga,
 * supaya ketiga jalur menghasilkan nilai yang sama persis.
 *
 * Semua fungsi mengembalikan "" bila nilai tidak dikenali (= dianggap belum diisi).
 */
class NormalisasiIdentitas
{
    public const AGAMA = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];

    public const STATUS_KAWIN = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'];

    public const GOL_DARAH = ['A', 'B', 'AB', 'O'];

    public const JENIS_KELAMIN = ['Laki-laki', 'Perempuan'];

    private static function besar(?string $s): string
    {
        return mb_strtoupper(trim(preg_replace('/\s+/u', ' ', (string) $s)));
    }

    public static function nik(?string $s): string
    {
        return preg_replace('/\D/', '', (string) $s);
    }

    /** KTP WNA memakai istilah Inggris (MALE/FEMALE). */
    public static function jenisKelamin(?string $s): string
    {
        $jk = self::besar($s);

        return match (true) {
            str_contains($jk, 'PEREMPUAN') || str_contains($jk, 'WANITA') || str_contains($jk, 'FEMALE') || $jk === 'P' => 'Perempuan',
            str_contains($jk, 'LAKI') || $jk === 'L' || $jk === 'PRIA' || $jk === 'MALE' => 'Laki-laki',
            default => '',
        };
    }

    public static function agama(?string $s): string
    {
        $a = self::besar($s);

        return match (true) {
            str_contains($a, 'ISLAM') || str_contains($a, 'MOSLEM') || str_contains($a, 'MUSLIM') => 'Islam',
            str_contains($a, 'KATOLIK') || str_contains($a, 'KATHOLIK') || str_contains($a, 'CATHOLIC') => 'Katolik',
            str_contains($a, 'KRISTEN') || str_contains($a, 'PROTESTAN') || str_contains($a, 'CHRISTIAN') => 'Kristen',
            str_contains($a, 'HINDU') => 'Hindu',
            str_contains($a, 'BUDHA') || str_contains($a, 'BUDDHA') => 'Buddha',
            str_contains($a, 'KONGHUCU') || str_contains($a, 'KHONGHUCU') || str_contains($a, 'CONFUCIAN') => 'Konghucu',
            default => '',
        };
    }

    public static function statusKawin(?string $s): string
    {
        $st = self::besar($s);

        return match (true) {
            str_contains($st, 'BELUM') || str_contains($st, 'NOT MARRIED') || str_contains($st, 'SINGLE') => 'Belum Kawin',
            str_contains($st, 'CERAI MATI') || str_contains($st, 'WIDOW') => 'Cerai Mati',
            str_contains($st, 'CERAI') || str_contains($st, 'DIVORCE') => 'Cerai Hidup',
            str_contains($st, 'KAWIN') || str_contains($st, 'MARRIED') => 'Kawin',
            default => '',
        };
    }

    /** "-" / "TIDAK TAHU" → "". */
    public static function golonganDarah(?string $s): string
    {
        $d = preg_replace('/[^ABO]/', '', self::besar($s));

        return in_array($d, self::GOL_DARAH, true) ? $d : '';
    }

    /** Kosong / WNI / Indonesia → "WNI"; selain itu teks aslinya (nama negara WNA). */
    public static function kewarganegaraan(?string $s): string
    {
        $wn = self::besar($s);

        return ($wn === '' || str_contains($wn, 'WNI') || str_contains($wn, 'INDONESIA')) ? 'WNI' : mb_substr($wn, 0, 30);
    }

    /** DD-MM-YYYY / DD/MM/YYYY / YYYY-MM-DD → Y-m-d; tanggal tidak valid → "". */
    public static function tanggal(?string $teks): string
    {
        $teks = trim((string) $teks);
        if (preg_match('/^(\d{1,2})\D(\d{1,2})\D(\d{4})$/', $teks, $m)) {
            [$h, $b, $t] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{4})\D(\d{1,2})\D(\d{1,2})$/', $teks, $m)) {
            [$t, $b, $h] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return '';
        }

        return checkdate($b, $h, $t) && $t >= 1900 && $t <= (int) now()->year
            ? sprintf('%04d-%02d-%02d', $t, $b, $h)
            : '';
    }

    /** Label pendidikan KK/DKB ("SLTA/SEDERAJAT", "AKADEMI/DIPLOMA III/S. MUDA", …) → pilihan baku IsianWarga. */
    public static function pendidikan(?string $s): string
    {
        // Tanda baca jadi spasi (BUKAN dihapus): "DIPLOMA I/II" tidak boleh menjadi "DIPLOMAIII" (= D3).
        $p = trim(preg_replace('/[^A-Z0-9]+/', ' ', self::besar($s)));
        if ($p === '') {
            return '';
        }
        $p = " {$p} ";
        $ada = fn (string ...$kata) => collect($kata)->contains(fn ($k) => str_contains($p, " {$k} "));

        return match (true) {
            $ada('BELUM TAMAT') => 'Belum Tamat SD/Sederajat',
            $ada('TIDAK', 'BLM SEKOLAH', 'BELUM SEKOLAH') => 'Tidak/Belum Sekolah',
            $ada('STRATA III', 'STRATA 3', 'S3') => 'Strata III',
            $ada('STRATA II', 'STRATA 2', 'S2') => 'Strata II',
            $ada('DIPLOMA IV', 'STRATA I', 'STRATA 1', 'S1', 'D4') => 'Diploma IV/Strata I',
            $ada('AKADEMI', 'DIPLOMA III', 'DIPL III', 'S MUDA', 'SARJANA MUDA', 'D3') => 'Akademi/Diploma III/Sarjana Muda',
            $ada('DIPLOMA I', 'DIPLOMA II', 'D1', 'D2') => 'Diploma I/II',
            $ada('SLTA', 'SMA', 'SMK', 'SMU', 'STM', 'MA') => 'SLTA/Sederajat',
            $ada('SLTP', 'SMP', 'MTS') => 'SLTP/Sederajat',
            $ada('SD', 'MI') => 'Tamat SD/Sederajat',
            default => '',
        };
    }

    public static function disabilitas(?string $s): string
    {
        $d = self::besar($s);
        if ($d === '' || $d === '-') {
            return '';
        }

        return match (true) {
            str_contains($d, 'TIDAK') || $d === 'NORMAL' => 'Tidak Ada',
            str_contains($d, 'FISIK') && str_contains($d, 'MENTAL') => 'Fisik dan Mental',
            str_contains($d, 'NETRA') || str_contains($d, 'BUTA') => 'Netra/Buta',
            str_contains($d, 'RUNGU') || str_contains($d, 'WICARA') || str_contains($d, 'TULI') || str_contains($d, 'BISU') => 'Rungu/Wicara',
            str_contains($d, 'MENTAL') || str_contains($d, 'JIWA') => 'Mental/Jiwa',
            str_contains($d, 'FISIK') || str_contains($d, 'DAKSA') => 'Fisik',
            default => 'Lainnya',
        };
    }

    /** Status hubungan dalam keluarga di KK ("KEPALA KELUARGA", "ISTRI", "ANAK", …) → label anggota. */
    public static function hubungan(?string $s): string
    {
        $h = self::besar($s);

        return $h === '' ? '' : mb_substr(mb_convert_case(mb_strtolower($h), MB_CASE_TITLE), 0, 100);
    }

    /**
     * Cek silang NIK (digit 7–12 = DDMMYY lahir, tanggal +40 untuk perempuan).
     *
     * @return array<int, string> peringatan untuk diperiksa ketua
     */
    public static function cekNik(string $nik, string $jenisKelamin, string $tanggalLahir): array
    {
        $peringatan = [];
        if ($nik === '') {
            return $peringatan;
        }
        if (strlen($nik) !== 16) {
            return ['NIK terbaca ' . strlen($nik) . ' digit (seharusnya 16). Periksa kembali.'];
        }

        $hari = (int) substr($nik, 6, 2);
        $nikPerempuan = $hari > 40;
        if ($jenisKelamin !== '' && $nikPerempuan !== ($jenisKelamin === 'Perempuan')) {
            $peringatan[] = 'Jenis kelamin tidak sesuai dengan kode NIK. Periksa NIK atau jenis kelamin.';
        }
        if ($tanggalLahir !== '') {
            $kodeNik = sprintf('%02d', $nikPerempuan ? $hari - 40 : $hari) . substr($nik, 8, 4);
            if ($kodeNik !== Carbon::parse($tanggalLahir)->format('dmy')) {
                $peringatan[] = 'Tanggal lahir tidak sesuai dengan kode NIK. Periksa NIK atau tanggal lahir.';
            }
        }

        return $peringatan;
    }
}
