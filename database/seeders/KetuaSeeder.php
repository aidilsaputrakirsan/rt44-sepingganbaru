<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder khusus untuk membuat akun Ketua RT.
 *
 * AMAN UNTUK PRODUCTION:
 * - Hanya menjalankan firstOrCreate (idempotent).
 * - Tidak ada truncate, delete, atau modifikasi data lain.
 * - Kalau ketua@rt44.com sudah ada, seeder skip (tidak ditimpa).
 *
 * Cara pakai:
 *   php artisan db:seed --class=KetuaSeeder
 */
class KetuaSeeder extends Seeder
{
    public function run(): void
    {
        $ketua = User::firstOrCreate(
            ['email' => 'ketua@rt44.com'],
            [
                'name' => 'Ketua RT-44',
                'password' => Hash::make('password'),
                'role' => 'ketua',
                'no_rumah' => 'KANTOR',
            ]
        );

        if ($ketua->wasRecentlyCreated) {
            $this->command->info("✓ Ketua user dibuat — email: ketua@rt44.com, password: password");
        } else {
            $this->command->warn("ℹ Ketua user sudah ada (id={$ketua->id}, role={$ketua->role}). Tidak ada yang diubah.");
        }
    }
}
