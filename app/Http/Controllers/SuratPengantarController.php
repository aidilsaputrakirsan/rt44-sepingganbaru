<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\ResidentIdCard;
use App\Models\ResidentProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
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

        return Inertia::render('Ketua/SuratPengantar', [
            'houses' => $houses,
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
            'person_id'          => 'nullable|string|max:20',
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
            'nomor_surat'        => 'nullable|string|max:50',
            'tanggal_surat'      => 'required|date',
        ]);

        $house = House::findOrFail($validated['house_id']);

        // Simpan-balik identitas ke data warga (anggota keluarga) bila ada orang terpilih
        $this->syncPersonData($house, $validated);

        $tanggalLahirFormatted = \Carbon\Carbon::parse($validated['tanggal_lahir'])->translatedFormat('d F Y');
        $tanggalSuratFormatted = \Carbon\Carbon::parse($validated['tanggal_surat'])->translatedFormat('d F Y');

        $pdf = Pdf::loadView('reports.surat-pengantar', [
            'data'                => $validated,
            'house'               => $house,
            'tanggal_lahir_fmt'   => $tanggalLahirFormatted,
            'tanggal_surat_fmt'   => $tanggalSuratFormatted,
            'ketua_name'          => $user->name,
        ])->setPaper('a4', 'portrait');

        $filename = 'SuratPengantar_' . str_replace('/', '-', $house->blok . $house->nomor) . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Simpan identitas dari form surat ke data warga (resident_id_cards),
     * supaya pembuatan surat berikutnya otomatis terisi.
     *
     * person_id: "cNN" = update kartu yang ada, "uNN" = buat kartu baru utk akun.
     */
    private function syncPersonData(House $house, array $data): void
    {
        $personId = $data['person_id'] ?? '';
        if (!$personId) {
            return;
        }

        $identity = [
            'nama'              => $data['nama_lengkap'],
            'nomor_ktp'         => $data['nik'],
            'jenis_kelamin'     => $data['jenis_kelamin'],
            'tempat_lahir'      => $data['tempat_lahir'],
            'tanggal_lahir'     => $data['tanggal_lahir'],
            'status_perkawinan' => $data['status_perkawinan'],
            'agama'             => $data['agama'],
            'pekerjaan'         => $data['pekerjaan'],
            'golongan_darah'    => $data['golongan_darah'] ?? null,
            'kewarganegaraan'   => $data['kewarganegaraan'],
        ];

        $prefix = substr($personId, 0, 1);
        $id = (int) substr($personId, 1);

        if ($prefix === 'c') {
            // Update kartu yang ada — pastikan kartu memang milik pemilik/penyewa rumah ini
            $card = ResidentIdCard::find($id);
            if ($card && $this->cardBelongsToHouse($card, $house)) {
                $card->update($identity);
                $this->syncNomorKk($card->resident_profile_id, $data['nomor_kk'] ?? null);
            }
            return;
        }

        if ($prefix === 'u') {
            // Buat kartu baru untuk akun pemilik/penyewa rumah ini
            $targetUser = collect([$house->owner, $house->tenant])
                ->filter()
                ->firstWhere('id', $id);
            if (!$targetUser) {
                return;
            }
            $profile = ResidentProfile::firstOrCreate(['user_id' => $targetUser->id]);
            ResidentIdCard::create(array_merge($identity, [
                'resident_profile_id' => $profile->id,
                'file_path' => null,
            ]));
            $this->syncNomorKk($profile->id, $data['nomor_kk'] ?? null);
        }
    }

    /** Pastikan kartu terkait pemilik/penyewa rumah ini (cegah update lintas rumah). */
    private function cardBelongsToHouse(ResidentIdCard $card, House $house): bool
    {
        $ownerProfileId = $house->owner?->residentProfile?->id;
        $tenantProfileId = $house->tenant?->residentProfile?->id;

        return in_array($card->resident_profile_id, array_filter([$ownerProfileId, $tenantProfileId]), true);
    }

    /** Simpan nomor KK ke profil bila diisi dan profil belum punya. */
    private function syncNomorKk(int $profileId, ?string $nomorKk): void
    {
        if (!$nomorKk) {
            return;
        }
        $profile = ResidentProfile::find($profileId);
        if ($profile && !$profile->nomor_kk) {
            $profile->update(['nomor_kk' => $nomorKk]);
        }
    }
}
