<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\LetterNumber;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class SuratPengantarController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (!in_array($user->role, ['ketua', 'admin', 'demo'])) {
            abort(403);
        }

        $houses = House::with([
                'owner.residentProfile.idCards',
                'tenant.residentProfile.idCards',
            ])
            ->orderByRaw("REGEXP_SUBSTR(blok, '^[A-Za-z]+'), CAST(REGEXP_SUBSTR(blok, '[0-9]+') AS UNSIGNED), CAST(nomor AS UNSIGNED)")
            ->get()
            ->map(fn($h) => [
                'id'     => $h->id,
                'label'  => $h->blok . '/' . $h->nomor,
                'blok'   => $h->blok,
                'nomor'  => $h->nomor,
                'status_huni' => $h->status_huni,
                'alamat' => 'Perumahan Sepinggan Pratama Blok ' . $h->blok . ' No. ' . $h->nomor
                            . ', Kel. Sepinggan Baru, Kec. Balikpapan Selatan',
                'people' => $this->collectPeople($h),
            ]);

        $tahun = (int) now()->year;
        $bulan = (int) now()->month;
        $nextUrut = LetterNumber::nextNomorUrut($tahun);

        return Inertia::render('Ketua/SuratPengantar', [
            'houses' => $houses,
            'nextNumber' => [
                'nomor_format' => LetterNumber::format($nextUrut, $bulan, $tahun),
            ],
        ]);
    }

    /**
     * Kumpulkan daftar orang (anggota keluarga) dari profil pemilik & penyewa.
     * Tiap resident_id_card = 1 orang dengan identitas tersimpan.
     */
    private function collectPeople(House $house): array
    {
        $people = [];

        foreach ([['owner', 'Pemilik'], ['tenant', 'Penyewa']] as [$rel, $slotLabel]) {
            $user = $house->{$rel};
            if (!$user) {
                continue;
            }

            $cards = $user->residentProfile?->idCards ?? collect();
            $nomorKk = $user->residentProfile->nomor_kk ?? '';

            // Fallback: pemilik/penyewa tanpa kartu pun tetap muncul dari nama akun
            if ($cards->isEmpty()) {
                $people[] = [
                    'id'                => 'u' . $user->id,
                    'slot_label'        => $slotLabel,
                    'nama'              => $user->name,
                    'nomor_ktp'         => '',
                    'nomor_kk'          => $nomorKk,
                    'jenis_kelamin'     => '',
                    'tempat_lahir'      => '',
                    'tanggal_lahir'     => '',
                    'status_perkawinan' => '',
                    'agama'             => '',
                    'pekerjaan'         => '',
                    'golongan_darah'    => '',
                    'kewarganegaraan'   => 'WNI',
                ];
                continue;
            }

            foreach ($cards as $card) {
                // Prioritas nama: kolom nama → nama akun warga (bukan label,
                // karena label itu relasi spt "kepala keluarga", bukan nama orang)
                $nama = $card->nama ?: $user->name;
                $people[] = [
                    'id'                => 'c' . $card->id,
                    'slot_label'        => $slotLabel . ($card->label ? ' - ' . $card->label : ''),
                    'nama'              => $nama,
                    'nomor_ktp'         => $card->nomor_ktp ?? '',
                    'nomor_kk'          => $nomorKk,
                    'jenis_kelamin'     => $card->jenis_kelamin ?? '',
                    'tempat_lahir'      => $card->tempat_lahir ?? '',
                    'tanggal_lahir'     => $card->tanggal_lahir?->format('Y-m-d') ?? '',
                    'status_perkawinan' => $card->status_perkawinan ?? '',
                    'agama'             => $card->agama ?? '',
                    'pekerjaan'         => $card->pekerjaan ?? '',
                    'golongan_darah'    => $card->golongan_darah ?? '',
                    'kewarganegaraan'   => $card->kewarganegaraan ?: 'WNI',
                ];
            }
        }

        return $people;
    }

    public function generate(Request $request)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['ketua', 'admin', 'demo'])) {
            abort(403);
        }

        $validated = $request->validate([
            'house_id'           => 'required|exists:houses,id',
            'nama_lengkap'       => 'required|string|max:100',
            'jenis_kelamin'      => 'required|in:Laki-laki,Perempuan',
            'tempat_lahir'       => 'required|string|max:100',
            'tanggal_lahir'      => 'required|date',
            'status_perkawinan'  => 'required|string|max:50',
            'agama'              => 'required|string|max:50',
            'pekerjaan'          => 'required|string|max:100',
            'golongan_darah'     => 'nullable|string|max:5',
            'kewarganegaraan'    => 'required|string|max:50',
            'alamat'             => 'required|string|max:200',
            'nik'                => 'required|string|max:20',
            'nomor_kk'           => 'required|string|max:20',
            'maksud_tujuan'      => 'required|string|max:200',
            'keperluan'          => 'nullable|array',
            'keperluan_lain'     => 'nullable|string|max:100',
            'alamat_dituju'      => 'nullable|string|max:200',
            'nomor_dituju'       => 'nullable|string|max:20',
            'rt_dituju'          => 'nullable|string|max:10',
            'kelurahan_dituju'   => 'nullable|string|max:100',
            'kecamatan_dituju'   => 'nullable|string|max:100',
            'kab_kota_dituju'    => 'nullable|string|max:100',
            'provinsi_dituju'    => 'nullable|string|max:100',
            'jumlah_pengikut'    => 'nullable|integer|min:0',
            'pengikut'           => 'nullable|array',
            'pengikut.*.nama'    => 'required_with:pengikut|string|max:100',
            'pengikut.*.hub_keluarga' => 'required_with:pengikut|string|max:50',
            'tanggal_surat'      => 'required|date',
        ]);

        $house = House::findOrFail($validated['house_id']);

        // Catatan: Surat Pengantar sengaja TIDAK menulis ke data warga (read-only).
        // Input/edit identitas warga dilakukan di Data Warga → Profil agar pembagian
        // pemilik/penyewa jelas dan tidak ambigu.

        // Catat nomor surat ke Agenda Surat (reset tiap tahun) lalu pakai di PDF
        $nomorSurat = $this->registerLetterNumber($validated);

        $tanggalLahirFormatted = \Carbon\Carbon::parse($validated['tanggal_lahir'])->locale('id')->translatedFormat('d F Y');
        $tanggalSuratFormatted = \Carbon\Carbon::parse($validated['tanggal_surat'])->locale('id')->translatedFormat('d F Y');

        $pdf = Pdf::loadView('reports.surat-pengantar', [
            'data'                => $validated,
            'house'               => $house,
            'nomor_surat_text'    => $nomorSurat,
            'tanggal_lahir_fmt'   => $tanggalLahirFormatted,
            'tanggal_surat_fmt'   => $tanggalSuratFormatted,
            'ketua_name'          => $user->name,
        ])->setPaper('a4', 'portrait');

        $filename = 'SuratPengantar_' . str_replace('/', '-', $house->blok . $house->nomor) . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Catat nomor surat baru ke Agenda Surat berdasarkan tanggal surat,
     * lalu kembalikan nomor terformat (001/RT.44/VI/2026).
     */
    private function registerLetterNumber(array $data): string
    {
        $tanggal = Carbon::parse($data['tanggal_surat']);
        $tahun = (int) $tanggal->year;
        $bulan = (int) $tanggal->month;
        $nomorUrut = LetterNumber::nextNomorUrut($tahun);

        LetterNumber::create([
            'nomor_urut' => $nomorUrut,
            'tahun'      => $tahun,
            'bulan'      => $bulan,
            'jenis'      => 'Surat Pengantar',
            'keterangan' => 'Surat Pengantar a.n. ' . $data['nama_lengkap'],
            'tanggal'    => $tanggal->toDateString(),
            'created_by' => auth()->id(),
        ]);

        return LetterNumber::format($nomorUrut, $bulan, $tahun);
    }
}
