<?php

namespace App\Http\Controllers;

use App\Models\House;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DemografiController extends Controller
{
    /**
     * Kategori usia (Pasal kependudukan RT). Batas atas eksklusif.
     * Umur dihitung dari tanggal_lahir vs hari ini → kategori auto-update tiap hari,
     * tanpa perlu kolom umur tersimpan / cron.
     */
    private const KATEGORI = [
        ['key' => 'baduta',     'label' => 'Baduta',      'desc' => '0 - 2 tahun',    'min' => 0,  'max' => 2,   'color' => 'pink'],
        ['key' => 'balita',     'label' => 'Balita',      'desc' => '2 - 5 tahun',    'min' => 2,  'max' => 5,   'color' => 'rose'],
        ['key' => 'anak',       'label' => 'Anak',        'desc' => '5 - 12 tahun',   'min' => 5,  'max' => 12,  'color' => 'amber'],
        ['key' => 'remaja',     'label' => 'Remaja',      'desc' => '12 - 20 tahun',  'min' => 12, 'max' => 20,  'color' => 'lime'],
        ['key' => 'dewasa',     'label' => 'Dewasa',      'desc' => '20 - 50 tahun',  'min' => 20, 'max' => 50,  'color' => 'emerald'],
        ['key' => 'pra_lansia', 'label' => 'Pra Lansia',  'desc' => '50 - 60 tahun',  'min' => 50, 'max' => 60,  'color' => 'sky'],
        ['key' => 'lansia',     'label' => 'Lansia',      'desc' => '60 tahun ke atas','min' => 60, 'max' => 200, 'color' => 'violet'],
    ];

    public function index()
    {
        $user = auth()->user();
        if ($user->role !== 'ketua') {
            abort(403, 'Halaman ini hanya untuk Ketua RT.');
        }

        $today = Carbon::today();

        // Siapkan akumulator per kategori
        $cats = [];
        foreach (self::KATEGORI as $k) {
            $cats[$k['key']] = array_merge($k, [
                'count' => 0, 'L' => 0, 'P' => 0, 'lainnya' => 0, 'people' => [],
            ]);
        }

        $totalAnggota   = 0; // total baris anggota (id_cards)
        $terdata        = 0; // punya tanggal_lahir valid
        $belumTerdata   = 0; // tanpa tanggal_lahir
        $totalL = 0; $totalP = 0;

        // Akumulator agama
        $agama = [];        // label => count
        $agamaBelum = 0;    // tanpa agama

        $houses = House::with([
                'owner.residentProfile.idCards',
                'tenant.residentProfile.idCards',
            ])
            ->orderByRaw("REGEXP_SUBSTR(blok, '^[A-Za-z]+'), CAST(REGEXP_SUBSTR(blok, '[0-9]+') AS UNSIGNED), CAST(nomor AS UNSIGNED)")
            ->get();

        foreach ($houses as $h) {
            $rumah = $h->blok . '/' . $h->nomor;

            foreach (['owner' => 'Pemilik', 'tenant' => 'Kontrak'] as $rel => $slot) {
                $person = $h->{$rel};
                if (!$person || !$person->residentProfile) {
                    continue;
                }

                foreach ($person->residentProfile->idCards as $card) {
                    $totalAnggota++;

                    $jk = $this->normalizeGender($card->jenis_kelamin);
                    if ($jk === 'L') $totalL++;
                    elseif ($jk === 'P') $totalP++;

                    // Komposisi agama
                    $ag = $this->normalizeAgama($card->agama);
                    if ($ag) {
                        $agama[$ag] = ($agama[$ag] ?? 0) + 1;
                    } else {
                        $agamaBelum++;
                    }

                    if (!$card->tanggal_lahir) {
                        $belumTerdata++;
                        continue;
                    }

                    // Hitung umur akurat: tahun + sisa bulan (bukan pembulatan).
                    $birth = Carbon::parse($card->tanggal_lahir);
                    $diff = $birth->diff($today);
                    $umurTahun = $diff->y;
                    $umurBulan = $diff->m;
                    $terdata++;

                    $catKey = $this->kategoriUntukUmur($umurTahun);
                    if (!$catKey) {
                        continue;
                    }

                    $c = &$cats[$catKey];
                    $c['count']++;
                    if ($jk === 'L') $c['L']++;
                    elseif ($jk === 'P') $c['P']++;
                    else $c['lainnya']++;

                    $c['people'][] = [
                        'nama'        => $card->nama ?: ($card->label ?: '(tanpa nama)'),
                        'umur_tahun'  => $umurTahun,
                        'umur_bulan'  => $umurBulan,
                        'umur_total_bulan' => $umurTahun * 12 + $umurBulan, // untuk sort presisi
                        'jk'          => $jk,
                        'rumah'       => $rumah,
                        'slot'        => $slot,
                    ];
                    unset($c);
                }
            }
        }

        // Susun output kategori terurut + sort people by umur
        $kategori = [];
        foreach (self::KATEGORI as $k) {
            $c = $cats[$k['key']];
            usort($c['people'], fn($a, $b) => $a['umur_total_bulan'] <=> $b['umur_total_bulan']);
            $kategori[] = $c;
        }

        // Susun komposisi agama: urutan baku dulu, sisanya (Lainnya) di belakang.
        $agamaMeta = [
            'Islam'     => ['color' => 'emerald', 'icon' => 'moon'],
            'Kristen'   => ['color' => 'sky',     'icon' => 'cross'],
            'Katolik'   => ['color' => 'blue',    'icon' => 'cross'],
            'Hindu'     => ['color' => 'amber',   'icon' => 'flame'],
            'Buddha'    => ['color' => 'orange',  'icon' => 'flower'],
            'Konghucu'  => ['color' => 'red',     'icon' => 'sparkles'],
            'Lainnya'   => ['color' => 'slate',   'icon' => 'users'],
        ];
        $agamaOut = [];
        foreach ($agamaMeta as $label => $meta) {
            if (!empty($agama[$label])) {
                $agamaOut[] = ['label' => $label, 'count' => $agama[$label]] + $meta;
                unset($agama[$label]);
            }
        }
        // Agama tak terduga (di luar daftar baku)
        foreach ($agama as $label => $count) {
            $agamaOut[] = ['label' => $label, 'count' => $count, 'color' => 'slate', 'icon' => 'users'];
        }

        return Inertia::render('Ketua/Demografi', [
            'kategori' => $kategori,
            'ringkasan' => [
                'total_anggota' => $totalAnggota,
                'terdata'       => $terdata,
                'belum_terdata' => $belumTerdata,
                'laki'          => $totalL,
                'perempuan'     => $totalP,
            ],
            'agama' => [
                'data'         => $agamaOut,
                'belum_terdata'=> $agamaBelum,
                'total'        => $totalAnggota,
            ],
            'generated_at' => $today->isoFormat('dddd, D MMMM Y'),
        ]);
    }

    private function kategoriUntukUmur(int $age): ?string
    {
        foreach (self::KATEGORI as $k) {
            if ($age >= $k['min'] && $age < $k['max']) {
                return $k['key'];
            }
        }
        return null;
    }

    private function normalizeAgama(?string $agama): ?string
    {
        if (!$agama) return null;
        $s = strtolower(trim($agama));
        if ($s === '' || $s === '-') return null;
        if (str_contains($s, 'islam') || $s === 'muslim') return 'Islam';
        if (str_contains($s, 'katol') || str_contains($s, 'katho')) return 'Katolik';
        if (str_contains($s, 'kristen') || str_contains($s, 'protestan')) return 'Kristen';
        if (str_contains($s, 'hindu')) return 'Hindu';
        if (str_contains($s, 'buddh') || str_contains($s, 'budha')) return 'Buddha';
        if (str_contains($s, 'konghucu') || str_contains($s, 'kong hu cu') || str_contains($s, 'khonghucu')) return 'Konghucu';
        // Selain itu: kapitalkan apa adanya
        return ucwords($s);
    }

    private function normalizeGender(?string $jk): ?string
    {
        if (!$jk) return null;
        $s = strtoupper(trim($jk));
        if (str_starts_with($s, 'L') || str_contains($s, 'LAKI')) return 'L';
        if (str_starts_with($s, 'P') || str_contains($s, 'PEREMPUAN') || str_starts_with($s, 'W')) return 'P';
        return null;
    }
}
