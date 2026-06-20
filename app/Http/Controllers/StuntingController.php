<?php

namespace App\Http\Controllers;

use App\Models\ChildMeasurement;
use App\Models\House;
use App\Models\ResidentIdCard;
use App\Services\StuntingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class StuntingController extends Controller
{
    public function __construct(private StuntingService $stunting) {}

    private function authorizeKetua(): void
    {
        if (auth()->user()->role !== 'ketua') {
            abort(403, 'Halaman ini hanya untuk Ketua RT.');
        }
    }

    public function index()
    {
        $this->authorizeKetua();
        $today = Carbon::today();

        // Ambil semua anggota (id_cards) + pengukurannya, lalu saring balita (<60 bln saat ini).
        $houses = House::with([
                'owner.residentProfile.idCards.measurements',
                'tenant.residentProfile.idCards.measurements',
            ])
            ->orderByRaw("REGEXP_SUBSTR(blok, '^[A-Za-z]+'), CAST(REGEXP_SUBSTR(blok, '[0-9]+') AS UNSIGNED), CAST(nomor AS UNSIGNED)")
            ->get();

        $balita = [];
        $sum = ['total' => 0, 'normal' => 0, 'stunting' => 0, 'severe' => 0, 'belum_ukur' => 0];

        foreach ($houses as $h) {
            $rumah = $h->blok . '/' . $h->nomor;
            foreach (['owner' => 'Pemilik', 'tenant' => 'Kontrak'] as $rel => $slot) {
                $person = $h->{$rel};
                if (!$person || !$person->residentProfile) continue;

                foreach ($person->residentProfile->idCards as $card) {
                    if (!$card->tanggal_lahir) continue;
                    $birth = Carbon::parse($card->tanggal_lahir);
                    $umurBulanKini = (int) $birth->diffInMonths($today);
                    if ($umurBulanKini >= StuntingService::USIA_MAKS_BULAN) continue; // bukan balita
                    $diffKini = $birth->diff($today);

                    $jk = $this->normalizeGender($card->jenis_kelamin);
                    $rows = $this->buildMeasurements($card, $jk);
                    $latest = end($rows) ?: null;

                    $sum['total']++;
                    if (!$latest) {
                        $sum['belum_ukur']++;
                    } else {
                        $lvl = $latest['haz']['kategori']['level'] ?? null;
                        if ($lvl === 'severe') $sum['severe']++;
                        elseif ($lvl === 'bad') $sum['stunting']++;
                        elseif ($lvl !== null) $sum['normal']++;
                    }

                    $balita[] = [
                        'id'           => $card->id,
                        'nama'         => $card->nama ?: ($card->label ?: '(tanpa nama)'),
                        'jk'           => $jk,
                        'rumah'        => $rumah,
                        'slot'         => $slot,
                        'tanggal_lahir'=> $card->tanggal_lahir->toDateString(),
                        'umur_bulan'   => $umurBulanKini,
                        'umur_fmt'     => $this->fmtUmur($diffKini->y, $diffKini->m, $diffKini->d),
                        'measurements' => $rows,
                        'latest'       => $latest ?: null,
                    ];
                }
            }
        }

        // total stunting = pendek + sangat pendek
        $sum['stunting_total'] = $sum['stunting'] + $sum['severe'];

        return Inertia::render('Ketua/Stunting', [
            'balita'       => $balita,
            'ringkasan'    => $sum,
            'generated_at' => $today->isoFormat('dddd, D MMMM Y'),
        ]);
    }

    /** Susun daftar pengukuran (urut tanggal) + z-score tiap baris. */
    private function buildMeasurements(ResidentIdCard $card, ?string $jk): array
    {
        $birth = Carbon::parse($card->tanggal_lahir);
        $rows = [];
        foreach ($card->measurements->sortBy('tanggal_ukur') as $m) {
            $tgl = Carbon::parse($m->tanggal_ukur);
            $umur = (int) $birth->diffInMonths($tgl);
            $du = $birth->diff($tgl);
            $bb = $m->berat_kg !== null ? (float) $m->berat_kg : null;
            $tb = $m->tinggi_cm !== null ? (float) $m->tinggi_cm : null;
            $z = ($jk && $bb && $tb)
                ? $this->stunting->nilai($jk, $umur, $bb, $tb, $m->cara_ukur)
                : ['haz' => null, 'waz' => null, 'wfl' => null];
            $rows[] = [
                'id'         => $m->id,
                'tanggal'    => $tgl->toDateString(),
                'tanggal_fmt'=> $tgl->isoFormat('D MMM Y'),
                'umur_bulan' => $umur,
                'umur_fmt'   => $this->fmtUmur($du->y, $du->m, $du->d),
                'berat_kg'   => $bb,
                'tinggi_cm'  => $tb,
                'lila_cm'            => $m->lila_cm !== null ? (float) $m->lila_cm : null,
                'lingkar_kepala_cm'  => $m->lingkar_kepala_cm !== null ? (float) $m->lingkar_kepala_cm : null,
                'vitamin_a'  => $m->vitamin_a,
                'asi_eksklusif' => $m->asi_eksklusif,
                'pmt_ke'     => $m->pmt_ke,
                'pmt_sumber' => $m->pmt_sumber,
                'cara_ukur'  => $m->cara_ukur,
                'catatan'    => $m->catatan,
                'haz'        => $z['haz'],
                'waz'        => $z['waz'],
                'wfl'        => $z['wfl'],
            ];
        }
        return $rows;
    }

    public function store(Request $request, ResidentIdCard $idCard)
    {
        $this->authorizeKetua();

        if (!$idCard->tanggal_lahir) {
            return back()->withErrors(['tanggal_ukur' => 'Anggota ini belum punya tanggal lahir, lengkapi dulu di Data Warga.']);
        }

        $data = $request->validate([
            'tanggal_ukur' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:' . $idCard->tanggal_lahir->toDateString()],
            'berat_kg'     => ['required', 'numeric', 'min:0.5', 'max:50'],
            'tinggi_cm'    => ['required', 'numeric', 'min:30', 'max:140'],
            'lila_cm'           => ['nullable', 'numeric', 'min:5', 'max:30'],
            'lingkar_kepala_cm' => ['nullable', 'numeric', 'min:20', 'max:60'],
            'vitamin_a'    => ['nullable', 'boolean'],
            'asi_eksklusif'=> ['nullable', 'array'],
            'asi_eksklusif.*' => ['integer', 'between:0,6'],
            'pmt_ke'       => ['nullable', 'integer', 'min:0', 'max:99'],
            'pmt_sumber'   => ['nullable', 'string', 'max:100'],
            'cara_ukur'    => ['required', 'in:berdiri,terlentang'],
            'catatan'      => ['nullable', 'string', 'max:255'],
        ]);

        $idCard->measurements()->create([
            ...$data,
            'recorded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Pengukuran tersimpan.');
    }

    public function destroy(ChildMeasurement $measurement)
    {
        $this->authorizeKetua();
        $measurement->delete();
        return back()->with('success', 'Pengukuran dihapus.');
    }

    /**
     * Unduh template Excel sesuai format yang dikenali sistem import.
     * Header kolom = kunci yang dibaca importer, agar tidak gagal saat di-upload kembali.
     */
    public function template()
    {
        $this->authorizeKetua();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Posyandu');

        // Kolom WAJIB cocok dengan importer (NAMA ANAK + NIK/TGL LAHIR untuk pencocokan).
        $headers = [
            'NO', 'NIK', 'NAMA ANAK', 'TGL LAHIR', 'JK', 'NAMA ORTU',
            'TANGGAL UKUR', 'TINGGI', 'CARA UKUR', 'BERAT', 'LILA', 'VITA',
            'LINGKAR KEPALA', 'ASI BULAN 0', 'ASI BULAN 1', 'ASI BULAN 2',
            'ASI BULAN 3', 'ASI BULAN 4', 'ASI BULAN 5', 'ASI BULAN 6',
            'PEMBERIAN KE', 'SUMBER PMT', 'KETERANGAN',
        ];

        // Baris judul + petunjuk singkat.
        $sheet->setCellValue('A1', 'TEMPLATE DATA POSYANDU BALITA — RT 44 SEPINGGAN BARU');
        $sheet->mergeCells('A1:W1');
        $sheet->setCellValue('A2', 'Petunjuk: isi mulai baris 5. NIK atau (NAMA ANAK + TGL LAHIR) dipakai mencocokkan ke Data Warga. CARA UKUR: berdiri / tidur. VITA & ASI BULAN: isi "YA" bila ya. Tanggal format YYYY-MM-DD.');
        $sheet->mergeCells('A2:W2');

        $colLetter = fn (int $i) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);

        // Header di baris 4.
        foreach ($headers as $idx => $h) {
            $sheet->setCellValue($colLetter($idx + 1) . '4', $h);
        }

        // Contoh 1 baris.
        $contoh = [
            '1', '6471030000000000', 'NAMA CONTOH', '2023-05-10', 'L', 'NAMA ORANG TUA',
            '2026-06-13', '82.5', 'berdiri', '10.4', '13.5', '',
            '46.0', '', '', '', '', '', '', '',
            '', '', 'sehat',
        ];
        foreach ($contoh as $idx => $v) {
            $sheet->setCellValue($colLetter($idx + 1) . '5', $v);
        }

        // Styling ringan header.
        $sheet->getStyle('A4:W4')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        foreach (range('A', 'W') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'template-posyandu-rt44.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import file Excel laporan Posyandu (format kader PKK).
     * Mencocokkan anak ke Data Warga via NIK lalu nama+tgl lahir.
     * Baris yang tidak cocok dilaporkan agar bisa ditambahkan manual.
     */
    public function import(Request $request)
    {
        $this->authorizeKetua();
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $sheet = IOFactory::load($request->file('file')->getRealPath())->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false); // index numerik 0-based

        // Cari baris header (mengandung 'NAMA ANAK' & 'TANGGALUKUR')
        $headerIdx = null; $map = [];
        foreach ($rows as $i => $r) {
            $norm = array_map(fn ($v) => $this->slug((string) $v), $r);
            if (in_array('namaanak', $norm) && in_array('tanggalukur', $norm)) {
                $headerIdx = $i;
                foreach ($norm as $col => $name) {
                    if ($name !== '') $map[$name] = $col;
                }
                break;
            }
        }
        if ($headerIdx === null) {
            return back()->withErrors(['file' => 'Format tidak dikenali: kolom "NAMA ANAK" / "TANGGALUKUR" tidak ditemukan.']);
        }

        $get = fn (array $r, string $key) => isset($map[$key]) ? ($r[$map[$key]] ?? null) : null;

        // Index pencarian anak (resident_id_cards yang punya tanggal lahir)
        $cards = ResidentIdCard::whereNotNull('tanggal_lahir')->get();
        $byNik = [];
        $byNamaTgl = [];
        foreach ($cards as $c) {
            if ($c->nomor_ktp) $byNik[$this->onlyDigits($c->nomor_ktp)] = $c;
            $byNamaTgl[$this->slug($c->nama) . '|' . $c->tanggal_lahir->toDateString()] = $c;
        }

        $masuk = 0; $update = 0; $takCocok = []; $dilewati = 0;

        foreach (array_slice($rows, $headerIdx + 1, null, true) as $r) {
            $nama = trim((string) $get($r, 'namaanak'));
            if ($nama === '') continue;

            $tglLahir = $this->parseTanggal($get($r, 'tgllahir'));
            $nik = $this->onlyDigits((string) $get($r, 'nik'));

            $card = ($nik && isset($byNik[$nik])) ? $byNik[$nik] : null;
            if (!$card && $tglLahir) {
                $card = $byNamaTgl[$this->slug($nama) . '|' . $tglLahir] ?? null;
            }
            if (!$card) {
                // fallback: cocokkan hanya by nama (kalau unik)
                $card = $byNamaTgl[$this->slug($nama) . '|' . ($tglLahir ?? '')] ?? null;
            }

            if (!$card) {
                $takCocok[] = $nama;
                continue;
            }

            $tglUkur = $this->parseTanggal($get($r, 'tanggalukur')) ?? Carbon::today()->toDateString();
            $payload = [
                'tanggal_ukur'      => $tglUkur,
                'berat_kg'          => $this->num($get($r, 'berat')),
                'tinggi_cm'         => $this->num($get($r, 'tinggi')),
                'lila_cm'           => $this->num($get($r, 'lila')),
                'lingkar_kepala_cm' => $this->num($get($r, 'lingkarkepala')),
                'vitamin_a'         => $this->ya($get($r, 'vita')),
                'asi_eksklusif'     => $this->asiBulan($r, $map),
                'pmt_ke'            => is_numeric($get($r, 'pemberianke')) ? (int) $get($r, 'pemberianke') : null,
                'pmt_sumber'        => $this->str($get($r, 'sumberpmt')),
                'cara_ukur'         => $this->caraUkur($get($r, 'caraukur')),
                'catatan'           => $this->str($get($r, 'keterangan')),
                'recorded_by'       => auth()->id(),
            ];

            // Upsert berdasarkan (anak, tanggal ukur) supaya import ulang tidak menggandakan.
            $existing = $card->measurements()->whereDate('tanggal_ukur', $tglUkur)->first();
            if ($existing) {
                $existing->update($payload);
                $update++;
            } else {
                $card->measurements()->create($payload);
                $masuk++;
            }
        }

        $msg = "Import selesai: {$masuk} baru, {$update} diperbarui.";
        if ($takCocok) {
            $msg .= ' ' . count($takCocok) . ' anak tidak ditemukan di Data Warga: ' . implode(', ', array_slice($takCocok, 0, 15)) . (count($takCocok) > 15 ? ', …' : '');
        }
        return back()->with('success', $msg);
    }

    // ---- Helper import ----

    private function slug(?string $s): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $s)));
    }

    private function onlyDigits(?string $s): string
    {
        return preg_replace('/\D/', '', (string) $s);
    }

    private function num($v): ?float
    {
        if ($v === null) return null;
        $v = str_replace(',', '.', trim((string) $v));
        return is_numeric($v) ? (float) $v : null; // "Tantrum" dll -> null
    }

    private function str($v): ?string
    {
        $v = trim((string) $v);
        return $v === '' ? null : mb_substr($v, 0, 255);
    }

    private function ya($v): ?bool
    {
        $s = strtolower(trim((string) $v));
        if ($s === '') return null;
        return in_array($s, ['ya', 'y', 'sudah', '1', 'true', 'ada']);
    }

    private function caraUkur($v): string
    {
        $s = strtolower(trim((string) $v));
        if (str_contains($s, 'tidur') || str_contains($s, 'terlentang') || str_contains($s, 'baring')) return 'terlentang';
        return 'berdiri';
    }

    private function asiBulan(array $r, array $map): ?array
    {
        $bulan = [];
        for ($b = 0; $b <= 6; $b++) {
            $key = 'asibulan' . $b;
            if (isset($map[$key]) && $this->ya($r[$map[$key]] ?? null)) {
                $bulan[] = $b;
            }
        }
        return $bulan ?: null;
    }

    private function parseTanggal($v): ?string
    {
        if ($v === null || $v === '') return null;
        // Angka serial Excel
        if (is_numeric($v)) {
            try { return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v))->toDateString(); }
            catch (\Throwable $e) {}
        }
        $s = trim((string) $v);
        // Format "22 - 05 - 2022" / "22-05-2022"
        if (preg_match('/^(\d{1,2})\s*-\s*(\d{1,2})\s*-\s*(\d{4})$/', $s, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        try { return Carbon::parse($s)->toDateString(); } catch (\Throwable $e) { return null; }
    }

    /** Format umur lengkap: "2 th 9 bln 5 hr" (bagian bernilai 0 disembunyikan). */
    private function fmtUmur(int $th, int $bln, int $hari): string
    {
        $parts = [];
        if ($th > 0)   $parts[] = "{$th} th";
        if ($bln > 0)  $parts[] = "{$bln} bln";
        if ($hari > 0) $parts[] = "{$hari} hr";
        return $parts ? implode(' ', $parts) : '0 hr';
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
