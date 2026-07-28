<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder khusus untuk membuat akun Ibu PKK.
 *
 * AMAN UNTUK PRODUCTION:
 * - Hanya menjalankan firstOrCreate (idempotent).
 * - Tidak ada truncate, delete, atau modifikasi data lain.
 * - Kalau pkk@rt44.com sudah ada, seeder skip (tidak ditimpa).
 *
 * Cara pakai:
 *   php artisan db:seed --class=PkkSeeder
 */
class PkkSeeder extends Seeder
{
    public function run(): void
    {
        $pkk = User::firstOrCreate(
            ['email' => 'pkk@rt44.com'],
            [
                'name' => 'Ibu PKK RT-44',
                'password' => Hash::make('password'),
                'role' => 'pkk',
                'no_rumah' => 'KANTOR',
            ]
        );

        if ($pkk->wasRecentlyCreated) {
            $this->command->info("✓ Ibu PKK user dibuat — email: pkk@rt44.com, password: password");
        } else {
            $this->command->warn("ℹ Ibu PKK user sudah ada (id={$pkk->id}, role={$pkk->role}). Tidak ada yang diubah.");
        }
    }
}
