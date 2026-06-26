<?php

namespace App\Http\Controllers;

use App\Models\LetterNumber;
use App\Models\MonthlyReport;
use App\Models\MonthlyReportActivity;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MonthlyReportController extends Controller
{
    private const PHOTO_DIR = 'laporan-bulanan';

    /** Batas sisi terpanjang foto (px). Lebih dari cukup utk kolom dokumentasi PDF (210/220px). */
    private const PHOTO_MAX_DIM = 1280;

    /** Kualitas re-encode JPEG (0-100). 80 = kompresi bagus, ukuran kecil, kualitas tetap layak cetak. */
    private const PHOTO_JPEG_QUALITY = 80;

    private function authorizeKetua(): void
    {
        if (auth()->user()?->role !== 'ketua') {
            abort(403);
        }
    }

    private function monthLabel(Carbon $period): string
    {
        return strtoupper($period->locale('id')->translatedFormat('F Y'));
    }

    // ── Daftar laporan ───────────────────────────────────────────
    public function index()
    {
        $this->authorizeKetua();

        $reports = MonthlyReport::withCount('activities')
            ->orderByDesc('period')
            ->get()
            ->map(fn (MonthlyReport $r) => [
                'id'              => $r->id,
                'period'          => $r->period->format('Y-m-d'),
                'bulan_label'     => $this->monthLabel($r->period),
                'activities_count' => $r->activities_count,
            ]);

        return Inertia::render('Ketua/LaporanBulanan/Index', [
            'reports' => $reports,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeKetua();

        $validated = $request->validate([
            'period' => 'required|date',
        ]);

        $period = Carbon::parse($validated['period'])->startOfMonth();

        $report = MonthlyReport::firstOrCreate(
            ['period' => $period->toDateString()],
            [
                'tanggal_pengesahan' => now()->toDateString(),
                'created_by'         => auth()->id(),
            ]
        );

        return redirect()->route('ketua.laporan-bulanan.show', $report->id);
    }

    // ── Editor laporan ───────────────────────────────────────────
    public function show(MonthlyReport $laporanBulanan)
    {
        $this->authorizeKetua();

        $laporanBulanan->load('activities');

        $letterOptions = LetterNumber::orderByDesc('tahun')
            ->orderByDesc('nomor_urut')
            ->get()
            ->map(fn (LetterNumber $l) => [
                'nomor'      => $l->nomor_format,
                'keterangan' => $l->keterangan,
            ]);

        return Inertia::render('Ketua/LaporanBulanan/Show', [
            'report' => [
                'id'                 => $laporanBulanan->id,
                'period'             => $laporanBulanan->period->format('Y-m-d'),
                'bulan_label'        => $this->monthLabel($laporanBulanan->period),
                'tanggal_pengesahan' => $laporanBulanan->tanggal_pengesahan?->format('Y-m-d'),
                'lurah_name'         => $laporanBulanan->lurah_name,
                'ketua_name'         => $laporanBulanan->ketua_name,
                'activities'         => $laporanBulanan->activities->map(fn ($a) => [
                    'id'          => $a->id,
                    'tanggal'     => $a->tanggal->format('Y-m-d'),
                    'uraian'      => $a->uraian,
                    'no_surat'    => $a->no_surat,
                    'photo_url'   => $a->photo_path ? Storage::url($a->photo_path) : null,
                    'orientation' => $a->photo_orientation,
                ]),
            ],
            'letterOptions' => $letterOptions,
        ]);
    }

    public function update(Request $request, MonthlyReport $laporanBulanan)
    {
        $this->authorizeKetua();

        $validated = $request->validate([
            'tanggal_pengesahan' => 'nullable|date',
            'lurah_name'         => 'required|string|max:100',
            'ketua_name'         => 'required|string|max:100',
        ]);

        $laporanBulanan->update($validated);

        return back()->with('success', 'Data laporan diperbarui.');
    }

    public function destroy(MonthlyReport $laporanBulanan)
    {
        $this->authorizeKetua();

        foreach ($laporanBulanan->activities as $a) {
            $this->deletePhoto($a->photo_path);
        }
        $laporanBulanan->delete();

        return redirect()->route('ketua.laporan-bulanan.index')->with('success', 'Laporan dihapus.');
    }

    // ── Kegiatan ─────────────────────────────────────────────────
    public function storeActivity(Request $request, MonthlyReport $laporanBulanan)
    {
        $this->authorizeKetua();

        $validated = $this->validateActivity($request);

        $data = [
            'monthly_report_id' => $laporanBulanan->id,
            'tanggal'           => $validated['tanggal'],
            'uraian'            => $validated['uraian'],
            'no_surat'          => $validated['no_surat'] ?? null,
            'sort_order'        => (int) $laporanBulanan->activities()->max('sort_order') + 1,
        ];

        if ($request->hasFile('photo')) {
            [$path, $orientation] = $this->storePhoto($request->file('photo'));
            $data['photo_path'] = $path;
            $data['photo_orientation'] = $orientation;
        }

        MonthlyReportActivity::create($data);

        return back()->with('success', 'Kegiatan ditambahkan.');
    }

    public function updateActivity(Request $request, MonthlyReport $laporanBulanan, MonthlyReportActivity $activity)
    {
        $this->authorizeKetua();
        abort_unless($activity->monthly_report_id === $laporanBulanan->id, 404);

        $validated = $this->validateActivity($request);

        $data = [
            'tanggal'  => $validated['tanggal'],
            'uraian'   => $validated['uraian'],
            'no_surat' => $validated['no_surat'] ?? null,
        ];

        if ($request->boolean('remove_photo') && !$request->hasFile('photo')) {
            $this->deletePhoto($activity->photo_path);
            $data['photo_path'] = null;
            $data['photo_orientation'] = null;
        }

        if ($request->hasFile('photo')) {
            $this->deletePhoto($activity->photo_path);
            [$path, $orientation] = $this->storePhoto($request->file('photo'));
            $data['photo_path'] = $path;
            $data['photo_orientation'] = $orientation;
        }

        $activity->update($data);

        return back()->with('success', 'Kegiatan diperbarui.');
    }

    public function destroyActivity(MonthlyReport $laporanBulanan, MonthlyReportActivity $activity)
    {
        $this->authorizeKetua();
        abort_unless($activity->monthly_report_id === $laporanBulanan->id, 404);

        $this->deletePhoto($activity->photo_path);
        $activity->delete();

        return back()->with('success', 'Kegiatan dihapus.');
    }

    private function validateActivity(Request $request): array
    {
        return $request->validate([
            'tanggal'  => 'required|date',
            'uraian'   => 'required|string|max:1000',
            'no_surat' => 'nullable|string|max:100',
            'photo'    => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
        ]);
    }

    /**
     * Simpan foto ke public disk + deteksi orientasi.
     * Foto besar otomatis dikecilkan (sisi terpanjang maks PHOTO_MAX_DIM) & dikompres
     * supaya ukuran file hemat — kualitas tetap lebih dari cukup utk tampil di PDF.
     * Return [path, orientation].
     */
    private function storePhoto($file): array
    {
        $realPath = $file->getRealPath();
        $info = @getimagesize($realPath);

        // Bukan gambar yang bisa dibaca GD → simpan apa adanya (fallback aman).
        if (! $info) {
            return [$file->store(self::PHOTO_DIR, 'public'), 'landscape'];
        }

        [$width, $height, $type] = $info;
        $orientation = $height > $width ? 'portrait' : 'landscape';

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($realPath),
            IMAGETYPE_PNG  => @imagecreatefrompng($realPath),
            default        => null,
        };

        // Tipe tak didukung GD (mis. format aneh) → simpan apa adanya.
        if (! $src) {
            return [$file->store(self::PHOTO_DIR, 'public'), $orientation];
        }

        // Hitung dimensi target — hanya dikecilkan bila melebihi batas (tidak pernah memperbesar).
        $longest = max($width, $height);
        $scale = $longest > self::PHOTO_MAX_DIM ? self::PHOTO_MAX_DIM / $longest : 1.0;
        $newW = max(1, (int) round($width * $scale));
        $newH = max(1, (int) round($height * $scale));

        $isPng = $type === IMAGETYPE_PNG;
        $filename = self::PHOTO_DIR . '/' . Str::uuid()->toString() . ($isPng ? '.png' : '.jpg');
        $fullPath = storage_path('app/public/' . $filename);

        if (! is_dir(dirname($fullPath))) {
            @mkdir(dirname($fullPath), 0775, true);
        }

        $dst = imagecreatetruecolor($newW, $newH);
        if ($isPng) {
            // Pertahankan transparansi PNG.
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        } else {
            // Latar putih utk jaga-jaga bila sumber punya alpha.
            imagefilledrectangle($dst, 0, 0, $newW, $newH, imagecolorallocate($dst, 255, 255, 255));
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);

        $isPng
            ? imagepng($dst, $fullPath, 6)
            : imagejpeg($dst, $fullPath, self::PHOTO_JPEG_QUALITY);

        imagedestroy($src);
        imagedestroy($dst);

        return [$filename, $orientation];
    }

    private function deletePhoto(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    // ── Export PDF ───────────────────────────────────────────────
    public function exportPdf(MonthlyReport $laporanBulanan)
    {
        $this->authorizeKetua();

        $laporanBulanan->load('activities');

        $activities = $laporanBulanan->activities->map(function ($a) {
            $imgPath = null;
            if ($a->photo_path) {
                $full = storage_path('app/public/' . $a->photo_path);
                if (is_file($full)) {
                    $imgPath = $full;
                }
            }
            return [
                'tanggal'     => $a->tanggal->format('d/m/Y'),
                'uraian'      => $a->uraian,
                'no_surat'    => $a->no_surat,
                'img_path'    => $imgPath,
                'orientation' => $a->photo_orientation,
            ];
        });

        $pdf = Pdf::loadView('reports.laporan-bulanan', [
            'bulan_label'        => $this->monthLabel($laporanBulanan->period),
            'tanggal_pengesahan' => $laporanBulanan->tanggal_pengesahan
                ? $laporanBulanan->tanggal_pengesahan->locale('id')->translatedFormat('j F Y')
                : '',
            'lurah_name'         => $laporanBulanan->lurah_name,
            'ketua_name'         => $laporanBulanan->ketua_name,
            'activities'         => $activities,
            'logo_path'          => public_path('images/logoberuang.png'),
            'logo_rt_path'       => public_path('logort.png'),
        ])->setPaper('a4', 'portrait');

        $filename = 'Laporan-Bulanan-' . $laporanBulanan->period->format('Y-m') . '.pdf';

        return $pdf->stream($filename);
    }
}
