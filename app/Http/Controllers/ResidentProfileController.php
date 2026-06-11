<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\ResidentIdCard;
use App\Models\ResidentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ResidentProfileController extends Controller
{
    private const MAX_KB = 5120;
    private const FILE_RULES = 'file|mimes:jpg,jpeg,png,pdf|max:5120';

    /** Aturan validasi field identitas anggota keluarga (semua opsional). */
    private const IDENTITY_RULES = [
        'label'             => 'nullable|string|max:100',
        'nama'              => 'nullable|string|max:100',
        'nomor_ktp'         => 'nullable|string|max:32',
        'jenis_kelamin'     => 'nullable|in:Laki-laki,Perempuan',
        'tempat_lahir'      => 'nullable|string|max:100',
        'tanggal_lahir'     => 'nullable|date',
        'status_perkawinan' => 'nullable|string|max:30',
        'agama'             => 'nullable|string|max:30',
        'pekerjaan'         => 'nullable|string|max:100',
        'golongan_darah'    => 'nullable|string|max:5',
        'kewarganegaraan'   => 'nullable|string|max:30',
    ];

    /** Ambil hanya field identitas dari data tervalidasi. */
    private function identityData(array $validated): array
    {
        return collect($validated)
            ->only(array_keys(self::IDENTITY_RULES))
            ->toArray();
    }

    /** Map satu ResidentIdCard untuk dikirim ke frontend (termasuk identitas). */
    private function mapIdCard(ResidentIdCard $c): array
    {
        return [
            'id' => $c->id,
            'label' => $c->label,
            'nama' => $c->nama,
            'nomor_ktp' => $c->nomor_ktp,
            'jenis_kelamin' => $c->jenis_kelamin,
            'tempat_lahir' => $c->tempat_lahir,
            'tanggal_lahir' => $c->tanggal_lahir?->format('Y-m-d'),
            'status_perkawinan' => $c->status_perkawinan,
            'agama' => $c->agama,
            'pekerjaan' => $c->pekerjaan,
            'golongan_darah' => $c->golongan_darah,
            'kewarganegaraan' => $c->kewarganegaraan,
            'file_path' => $c->file_path,
            'file_url' => $c->file_path ? Storage::url($c->file_path) : null,
        ];
    }

    public function show()
    {
        $user = auth()->user();

        if (in_array($user->role, ['admin', 'demo', 'ketua'])) {
            return redirect()->route('admin.dashboard');
        }

        $profile = ResidentProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->load('idCards');

        // Cari rumah lewat owner_id, lalu fallback ke tenant_id
        $house = $user->houses()->first() ?? $user->tenantedHouses()->first();

        return Inertia::render('Warga/Profile', [
            'profile' => [
                'id' => $profile->id,
                'jumlah_anggota_keluarga' => $profile->jumlah_anggota_keluarga,
                'nomor_kk' => $profile->nomor_kk,
                'kk_path' => $profile->kk_path,
                'kk_url' => $profile->kk_path ? Storage::url($profile->kk_path) : null,
                'id_cards' => $profile->idCards->map(fn ($c) => $this->mapIdCard($c)),
            ],
            'readonly_info' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'no_rumah' => $house ? $house->blok . '/' . $house->nomor : '-',
                'status_huni' => $house ? $house->status_huni : null,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        if (in_array($user->role, ['admin', 'demo', 'ketua'])) {
            abort(403);
        }

        $validated = $request->validate([
            'jumlah_anggota_keluarga' => 'nullable|integer|min:0|max:50',
            'nomor_kk' => 'nullable|string|max:32',
        ]);

        $profile = ResidentProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->update($validated);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function uploadKk(Request $request)
    {
        $user = auth()->user();

        if (in_array($user->role, ['admin', 'demo', 'ketua'])) {
            abort(403);
        }

        $request->validate([
            'kk_file' => self::FILE_RULES,
        ]);

        $profile = ResidentProfile::firstOrCreate(['user_id' => $user->id]);

        if ($profile->kk_path && Storage::disk('public')->exists($profile->kk_path)) {
            Storage::disk('public')->delete($profile->kk_path);
        }

        $path = $request->file('kk_file')->store("profiles/{$user->id}", 'public');
        $profile->update(['kk_path' => $path]);

        return back()->with('success', 'Kartu Keluarga berhasil diunggah.');
    }

    public function deleteKk()
    {
        $user = auth()->user();

        if (in_array($user->role, ['admin', 'demo', 'ketua'])) {
            abort(403);
        }

        $profile = $user->residentProfile;
        if (!$profile || !$profile->kk_path) {
            return back();
        }

        if (Storage::disk('public')->exists($profile->kk_path)) {
            Storage::disk('public')->delete($profile->kk_path);
        }
        $profile->update(['kk_path' => null]);

        return back()->with('success', 'Kartu Keluarga dihapus.');
    }

    public function uploadKtp(Request $request)
    {
        $user = auth()->user();

        if (in_array($user->role, ['admin', 'demo', 'ketua'])) {
            abort(403);
        }

        $validated = $request->validate(array_merge(self::IDENTITY_RULES, [
            'ktp_file' => 'nullable|' . self::FILE_RULES,
        ]));

        $profile = ResidentProfile::firstOrCreate(['user_id' => $user->id]);

        $path = $request->hasFile('ktp_file')
            ? $request->file('ktp_file')->store("profiles/{$user->id}", 'public')
            : null;

        ResidentIdCard::create(array_merge($this->identityData($validated), [
            'resident_profile_id' => $profile->id,
            'file_path' => $path,
        ]));

        return back()->with('success', 'Data anggota keluarga berhasil disimpan.');
    }

    public function updateKtp(Request $request, ResidentIdCard $idCard)
    {
        $user = auth()->user();

        if (in_array($user->role, ['admin', 'demo', 'ketua'])) {
            abort(403);
        }

        if ($idCard->profile->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate(self::IDENTITY_RULES);
        $idCard->update($this->identityData($validated));

        return back()->with('success', 'Data anggota keluarga diperbarui.');
    }

    public function deleteKtp(ResidentIdCard $idCard)
    {
        $user = auth()->user();

        if (in_array($user->role, ['admin', 'demo', 'ketua'])) {
            abort(403);
        }

        if ($idCard->profile->user_id !== $user->id) {
            abort(403);
        }

        if (Storage::disk('public')->exists($idCard->file_path)) {
            Storage::disk('public')->delete($idCard->file_path);
        }

        $idCard->delete();

        return back()->with('success', 'KTP dihapus.');
    }

    /**
     * Resolve user target untuk admin endpoint berdasarkan slot.
     * Return [user, slot] atau abort dengan error.
     */
    private function resolveSlotUser(Request $request, House $house): array
    {
        if (!in_array(auth()->user()->role, ['admin', 'demo', 'ketua'])) {
            abort(403);
        }
        $slot = $request->input('slot', 'owner');
        $target = $slot === 'tenant' ? $house->tenant : $house->owner;
        if (!$target) {
            abort(404, $slot === 'tenant' ? 'Rumah belum memiliki data kontrak.' : 'Rumah belum memiliki pemilik.');
        }
        return [$target, $slot];
    }

    public function adminUpdate(Request $request, House $house)
    {
        [$user, $slot] = $this->resolveSlotUser($request, $house);

        $validated = $request->validate([
            'jumlah_anggota_keluarga' => 'nullable|integer|min:0|max:50',
            'nomor_kk' => 'nullable|string|max:32',
        ]);

        $profile = ResidentProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->update($validated);

        return back()->with('success', 'Data profil berhasil diperbarui.');
    }

    public function adminUploadKk(Request $request, House $house)
    {
        [$user, $slot] = $this->resolveSlotUser($request, $house);

        $request->validate(['kk_file' => self::FILE_RULES]);
        $profile = ResidentProfile::firstOrCreate(['user_id' => $user->id]);

        if ($profile->kk_path && Storage::disk('public')->exists($profile->kk_path)) {
            Storage::disk('public')->delete($profile->kk_path);
        }
        $path = $request->file('kk_file')->store("profiles/{$user->id}", 'public');
        $profile->update(['kk_path' => $path]);

        return back()->with('success', 'Kartu Keluarga berhasil diunggah.');
    }

    public function adminDeleteKk(Request $request, House $house)
    {
        [$user, $slot] = $this->resolveSlotUser($request, $house);

        $profile = $user->residentProfile;
        if (!$profile || !$profile->kk_path) {
            return back();
        }
        if (Storage::disk('public')->exists($profile->kk_path)) {
            Storage::disk('public')->delete($profile->kk_path);
        }
        $profile->update(['kk_path' => null]);

        return back()->with('success', 'Kartu Keluarga dihapus.');
    }

    public function adminUploadKtp(Request $request, House $house)
    {
        [$user, $slot] = $this->resolveSlotUser($request, $house);

        $validated = $request->validate(array_merge(self::IDENTITY_RULES, [
            'ktp_file' => 'nullable|' . self::FILE_RULES,
        ]));

        $profile = ResidentProfile::firstOrCreate(['user_id' => $user->id]);
        $path = $request->hasFile('ktp_file')
            ? $request->file('ktp_file')->store("profiles/{$user->id}", 'public')
            : null;

        ResidentIdCard::create(array_merge($this->identityData($validated), [
            'resident_profile_id' => $profile->id,
            'file_path' => $path,
        ]));

        return back()->with('success', 'Data anggota keluarga berhasil disimpan.');
    }

    public function adminUpdateKtp(Request $request, House $house, ResidentIdCard $idCard)
    {
        [$user, $slot] = $this->resolveSlotUser($request, $house);

        if ($idCard->profile->user_id !== $user->id) {
            abort(403, 'KTP tidak terkait dengan slot ini.');
        }

        $validated = $request->validate(self::IDENTITY_RULES);
        $idCard->update($this->identityData($validated));

        return back()->with('success', 'Data anggota keluarga diperbarui.');
    }

    public function adminDeleteKtp(Request $request, House $house, ResidentIdCard $idCard)
    {
        [$user, $slot] = $this->resolveSlotUser($request, $house);

        // Pastikan KTP yang akan dihapus benar2 milik user di slot ini
        if ($idCard->profile->user_id !== $user->id) {
            abort(403, 'KTP tidak terkait dengan slot ini.');
        }

        if (Storage::disk('public')->exists($idCard->file_path)) {
            Storage::disk('public')->delete($idCard->file_path);
        }
        $idCard->delete();

        return back()->with('success', 'KTP dihapus.');
    }

    public function adminShow(Request $request, House $house)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['admin', 'demo', 'ketua'])) {
            return redirect()->route('dashboard');
        }

        $slot = $request->input('slot', 'owner'); // 'owner' | 'tenant'

        $targetUser = $slot === 'tenant' ? $house->tenant : $house->owner;
        if (!$targetUser) {
            $label = $slot === 'tenant' ? 'data kontrak' : 'pemilik';
            return back()->with('error', "Rumah ini belum memiliki {$label} terdaftar.");
        }

        $owner = $targetUser;
        $profile = ResidentProfile::with('idCards')->firstOrCreate(['user_id' => $owner->id]);

        return Inertia::render('Admin/Warga/Profile', [
            'house' => [
                'id' => $house->id,
                'blok' => $house->blok,
                'nomor' => $house->nomor,
                'status_huni' => $house->status_huni,
                'resident_status' => $house->resident_status,
            ],
            'slot' => $slot,
            'owner' => [
                'name' => $owner->name,
                'email' => $owner->email,
                'phone_number' => $owner->phone_number,
            ],
            'profile' => [
                'jumlah_anggota_keluarga' => $profile->jumlah_anggota_keluarga,
                'nomor_kk' => $profile->nomor_kk,
                'kk_path' => $profile->kk_path,
                'kk_url' => $profile->kk_path ? Storage::url($profile->kk_path) : null,
                'id_cards' => $profile->idCards->map(fn ($c) => $this->mapIdCard($c)),
            ],
        ]);
    }
}
