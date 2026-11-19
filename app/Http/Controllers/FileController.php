<?php

namespace App\Http\Controllers;

use App\Models\ResidentIdCard;
use App\Models\ResidentProfile;
use Illuminate\Support\Facades\Storage;

/**
 * Penyaji file SENSITIF (KTP, KK) yang disimpan di disk privat 'local'
 * (storage/app/private) — TIDAK bisa diakses lewat URL /storage.
 * Hanya boleh dibuka pemilik profil atau pengurus (admin/ketua).
 * Akun demo sengaja ditolak: dokumen berisi NIK warga sungguhan.
 */
class FileController extends Controller
{
    private function bolehBuka(?int $pemilikId): bool
    {
        $user = auth()->user();

        return $user && ($user->id === $pemilikId || in_array($user->role, ['admin', 'ketua'], true));
    }

    /** Stream file dari disk privat; 404 bila path kosong / file tidak ada. */
    private function sajikan(?string $path)
    {
        abort_if(! $path || ! Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function ktp(ResidentIdCard $idCard)
    {
        abort_unless($this->bolehBuka($idCard->profile?->user_id), 403);

        return $this->sajikan($idCard->file_path);
    }

    public function kk(ResidentProfile $profile)
    {
        abort_unless($this->bolehBuka($profile->user_id), 403);

        return $this->sajikan($profile->kk_path);
    }
}
