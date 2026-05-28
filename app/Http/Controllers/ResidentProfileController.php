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

    public function show()
    {
        $user = auth()->user();

        if (in_array($user->role, ['admin', 'demo'])) {
            return redirect()->route('admin.dashboard');
        }

        $profile = ResidentProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->load('idCards');

        $house = $user->houses()->first();

        return Inertia::render('Warga/Profile', [
            'profile' => [
                'id' => $profile->id,
                'jumlah_anggota_keluarga' => $profile->jumlah_anggota_keluarga,
                'kk_path' => $profile->kk_path,
                'kk_url' => $profile->kk_path ? Storage::url($profile->kk_path) : null,
                'id_cards' => $profile->idCards->map(fn ($c) => [
                    'id' => $c->id,
                    'label' => $c->label,
                    'file_path' => $c->file_path,
                    'file_url' => Storage::url($c->file_path),
                ]),
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

        if (in_array($user->role, ['admin', 'demo'])) {
            abort(403);
        }

        $validated = $request->validate([
            'jumlah_anggota_keluarga' => 'nullable|integer|min:0|max:50',
        ]);

        $profile = ResidentProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->update($validated);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function uploadKk(Request $request)
    {
        $user = auth()->user();

        if (in_array($user->role, ['admin', 'demo'])) {
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

        if (in_array($user->role, ['admin', 'demo'])) {
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

        if (in_array($user->role, ['admin', 'demo'])) {
            abort(403);
        }

        $validated = $request->validate([
            'label' => 'nullable|string|max:100',
            'ktp_file' => self::FILE_RULES,
        ]);

        $profile = ResidentProfile::firstOrCreate(['user_id' => $user->id]);

        $path = $request->file('ktp_file')->store("profiles/{$user->id}", 'public');

        ResidentIdCard::create([
            'resident_profile_id' => $profile->id,
            'label' => $validated['label'] ?? null,
            'file_path' => $path,
        ]);

        return back()->with('success', 'KTP berhasil diunggah.');
    }

    public function deleteKtp(ResidentIdCard $idCard)
    {
        $user = auth()->user();

        if (in_array($user->role, ['admin', 'demo'])) {
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

    public function adminShow(House $house)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['admin', 'demo'])) {
            return redirect()->route('dashboard');
        }

        if (!$house->owner_id) {
            return back()->with('error', 'Rumah ini belum memiliki pemilik terdaftar.');
        }

        $owner = $house->owner;
        $profile = ResidentProfile::with('idCards')->firstOrCreate(['user_id' => $owner->id]);

        return Inertia::render('Admin/Warga/Profile', [
            'house' => [
                'id' => $house->id,
                'blok' => $house->blok,
                'nomor' => $house->nomor,
                'status_huni' => $house->status_huni,
                'resident_status' => $house->resident_status,
            ],
            'owner' => [
                'name' => $owner->name,
                'email' => $owner->email,
                'phone_number' => $owner->phone_number,
            ],
            'profile' => [
                'jumlah_anggota_keluarga' => $profile->jumlah_anggota_keluarga,
                'kk_path' => $profile->kk_path,
                'kk_url' => $profile->kk_path ? Storage::url($profile->kk_path) : null,
                'id_cards' => $profile->idCards->map(fn ($c) => [
                    'id' => $c->id,
                    'label' => $c->label,
                    'file_url' => Storage::url($c->file_path),
                ]),
            ],
        ]);
    }
}
