<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\ResidentIdCard;
use App\Models\ResidentProfile;
use App\Services\Ai\AiTidakTersedia;
use App\Services\Ai\KtpTidakTerbaca;
use App\Services\Ai\PembacaKk;
use App\Services\Ai\PembacaKtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Input cepat anggota keluarga dari foto (port dari sistem sepingganbaru-rt):
 * - Foto Kartu Keluarga → dibaca AI → admin memeriksa → simpan semua anggota sekaligus.
 * - Foto KTP → dibaca AI → mengisi form Tambah Anggota (disimpan lewat endpoint KTP biasa).
 * Endpoint scan TIDAK menyimpan apa pun; foto hanya diteruskan ke OpenRouter (ZDR).
 * Akun demo tidak boleh memakai scan (kuota AI berbayar).
 */
class DataKeluargaController extends Controller
{
    /** Baca foto KTP satu orang → isian form (tidak menyimpan apa pun). */
    public function scanKtp(Request $request, PembacaKtp $pembaca)
    {
        $this->pastikanBolehScan();
        $request->validate(['foto' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192']);

        if (! $pembaca->aktif()) {
            return response()->json(['pesan' => 'Baca otomatis belum dikonfigurasi. Isi data secara manual.'], 503);
        }

        try {
            return response()->json($pembaca->baca($request->user(), $request->file('foto')));
        } catch (KtpTidakTerbaca $e) {
            return response()->json(['pesan' => $e->getMessage()], 422);
        } catch (AiTidakTersedia $e) {
            return response()->json(['pesan' => $e->getMessage() . ' Coba lagi sebentar, atau isi data secara manual.'], 503);
        }
    }

    /** Baca foto KK → daftar anggota untuk diperiksa (tidak menyimpan apa pun). */
    public function scanKk(Request $request, House $house, PembacaKk $pembaca)
    {
        $this->pastikanBolehScan();
        $request->validate(['foto' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192']);
        $user = $this->targetUser($request, $house);

        if (! $pembaca->aktif()) {
            return response()->json(['pesan' => 'Baca otomatis belum dikonfigurasi. Isi data secara manual.'], 503);
        }

        try {
            $hasil = $pembaca->bacaKk($request->user(), $request->file('foto'));
        } catch (KtpTidakTerbaca $e) {
            return response()->json(['pesan' => $e->getMessage()], 422);
        } catch (AiTidakTersedia $e) {
            return response()->json(['pesan' => $e->getMessage() . ' Coba lagi sebentar, atau isi data secara manual.'], 503);
        }

        // Tandai anggota yang sudah terdaftar (NIK/nama sama) → akan diperbarui, bukan dobel.
        $ada = ResidentProfile::where('user_id', $user->id)->first()?->idCards()->get(['id', 'nomor_ktp', 'nama']) ?? collect();
        foreach ($hasil['anggota'] as &$a) {
            $a['id'] = $this->cocokkan($ada, $a['data']['nomor_ktp'], $a['data']['nama'])?->id;
        }
        unset($a);

        return response()->json($hasil);
    }

    /** Simpan anggota hasil baca KK yang sudah diperiksa admin. */
    public function simpanKk(Request $request, House $house)
    {
        $user = $this->targetUser($request, $house);

        $aturan = collect(ResidentProfileController::identityRules())
            ->except(['alamat'])
            ->mapWithKeys(fn ($r, $k) => ["anggota.*.$k" => $r])
            ->all();

        $validated = $request->validate([
            'slot' => 'nullable|in:owner,tenant',
            'nomor_kk' => ['nullable', 'regex:/^\d{16}$/'],
            'simpan_foto' => 'nullable|boolean',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'anggota' => 'required|array|min:1|max:20',
            'anggota.*.id' => 'nullable|integer',
            'anggota.*.nama' => 'required|string|max:100',
        ] + $aturan, [
            'nomor_kk.regex' => 'Nomor KK harus 16 digit angka.',
            'anggota.*.nama.required' => 'Nama anggota ke-:position wajib diisi.',
        ]);

        $profile = ResidentProfile::firstOrCreate(['user_id' => $user->id]);
        $kolom = array_keys(ResidentProfileController::identityRules());

        [$baru, $diperbarui] = DB::transaction(function () use ($validated, $profile, $kolom, $request, $user) {
            $ada = $profile->idCards()->get();
            $baru = 0;
            $diperbarui = 0;

            foreach ($validated['anggota'] as $a) {
                $data = collect($a)->only($kolom)->map(fn ($v) => $v === '' ? null : $v)->all();
                $card = (! empty($a['id']) ? $ada->firstWhere('id', (int) $a['id']) : null)
                    ?? $this->cocokkan($ada, $data['nomor_ktp'] ?? '', $data['nama'] ?? '');

                if ($card) {
                    $card->update($data);
                    $diperbarui++;
                } else {
                    $ada->push($profile->idCards()->create($data));
                    $baru++;
                }
            }

            $ubah = ['jumlah_anggota_keluarga' => max((int) $profile->jumlah_anggota_keluarga, $profile->idCards()->count())];
            if (! empty($validated['nomor_kk'])) {
                $ubah['nomor_kk'] = $validated['nomor_kk'];
            }
            // Foto KK sekalian jadi lampiran KK (menggantikan lampiran lama) bila dicentang.
            if ($request->boolean('simpan_foto') && $request->hasFile('foto')) {
                if ($profile->kk_path && Storage::disk('local')->exists($profile->kk_path)) {
                    Storage::disk('local')->delete($profile->kk_path);
                }
                $ubah['kk_path'] = $request->file('foto')->store("profiles/{$user->id}", 'local');
            }
            $profile->update($ubah);

            return [$baru, $diperbarui];
        });

        $ringkas = implode(', ', array_filter([
            $baru ? "{$baru} anggota baru" : null,
            $diperbarui ? "{$diperbarui} anggota diperbarui" : null,
        ])) ?: 'tidak ada perubahan';

        return back()->with('success', "Data KK tersimpan: {$ringkas}.");
    }

    // ── Bantuan ─────────────────────────────────────────────────────────

    private function pastikanBolehScan(): void
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'ketua'], true), 403);
    }

    /** Pemilik atau kontrak rumah (sama dengan ResidentProfileController::resolveSlotUser). */
    private function targetUser(Request $request, House $house)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'demo', 'ketua'], true), 403);
        $slot = $request->input('slot') === 'tenant' ? 'tenant' : 'owner';
        $user = $house->{$slot};
        abort_unless($user, 404, $slot === 'tenant' ? 'Rumah belum memiliki data kontrak.' : 'Rumah belum memiliki pemilik.');

        return $user;
    }

    /** Anggota yang sudah ada: cocok NIK (bila ada), selain itu nama sama persis (abaikan huruf besar/kecil). */
    private function cocokkan($ada, ?string $nik, ?string $nama): ?ResidentIdCard
    {
        if ($nik) {
            if ($c = $ada->first(fn ($x) => $x->nomor_ktp === $nik)) {
                return $c;
            }
        }
        $nama = mb_strtoupper(trim((string) $nama));

        return $nama === '' ? null : $ada->first(fn ($x) => ! $x->nomor_ktp || ! $nik ? mb_strtoupper(trim((string) $x->nama)) === $nama : false);
    }
}
