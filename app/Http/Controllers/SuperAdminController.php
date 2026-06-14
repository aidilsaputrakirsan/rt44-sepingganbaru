<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SuperAdminController extends Controller
{
    /** Hanya role superadmin yang boleh mengakses panel ini. */
    private function guard(): void
    {
        if (auth()->user()?->role !== 'superadmin') {
            abort(403, 'Halaman ini hanya untuk Super Admin.');
        }
    }

    /** Buka password tersimpan (encrypted) dengan aman; null kalau belum ada/rusak. */
    private function revealPlain(?string $encrypted): ?string
    {
        if (!$encrypted) {
            return null;
        }
        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function index()
    {
        $this->guard();

        $accounts = User::whereIn('role', ['admin', 'ketua'])
            ->orderByRaw("FIELD(role, 'admin', 'ketua')")
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id'              => $u->id,
                'name'            => $u->name,
                'email'           => $u->email,
                'role'            => $u->role,
                'role_label'      => $u->role === 'admin' ? 'Bendahara' : 'Ketua',
                'phone_number'    => $u->phone_number,
                // password yang berlaku, sudah didekripsi. null = belum pernah di-set lewat panel ini.
                'password_plain'  => $this->revealPlain($u->password_plain),
            ]);

        return Inertia::render('SuperAdmin/Akun', [
            'accounts' => $accounts,
        ]);
    }

    /** Set / reset password sebuah akun admin/ketua, sekaligus simpan salinan terenkripsi. */
    public function updatePassword(Request $request, User $user)
    {
        $this->guard();

        if (!in_array($user->role, ['admin', 'ketua'])) {
            abort(403, 'Hanya akun admin/ketua yang dapat dikelola di sini.');
        }

        $validated = $request->validate([
            'password' => 'required|string|min:6|max:100',
        ]);

        $user->update([
            'password'       => Hash::make($validated['password']),
            'password_plain' => Crypt::encryptString($validated['password']),
        ]);

        return back()->with('success', "Password akun {$user->name} berhasil diperbarui.");
    }

    /** Edit identitas akun (nama, email, no HP) — tanpa menyentuh password. */
    public function updateProfile(Request $request, User $user)
    {
        $this->guard();

        if (!in_array($user->role, ['admin', 'ketua'])) {
            abort(403, 'Hanya akun admin/ketua yang dapat dikelola di sini.');
        }

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone_number' => 'nullable|string|max:50',
        ]);

        $user->update($validated);

        return back()->with('success', "Data akun {$user->name} berhasil diperbarui.");
    }
}
