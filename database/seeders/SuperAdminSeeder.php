<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder akun Super Admin (pengelola akun admin & ketua).
 *
 * .env adalah SUMBER KEBENARAN TUNGGAL untuk password super admin:
 * - Password diambil dari .env SUPERADMIN_PASSWORD (tidak di-hardcode, repo public aman).
 * - Seeder pakai updateOrCreate → tiap dijalankan, password DISINKRON ke nilai .env.
 *   Jadi cara ganti password = edit .env lalu jalankan ulang seeder.
 *   Lupa password = lihat saja file .env di server.
 * - Kalau SUPERADMIN_PASSWORD kosong, di-generate acak & ditampilkan SEKALI di terminal.
 *
 * Cara pakai:
 *   php artisan db:seed --class=SuperAdminSeeder
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $envPassword = env('SUPERADMIN_PASSWORD');
        $password = $envPassword ?: Str::random(14);

        $super = User::updateOrCreate(
            ['email' => 'superadmin@rt44.com'],
            [
                'name'           => 'Super Admin',
                'password'       => Hash::make($password),
                'password_plain' => Crypt::encryptString($password),
                'role'           => 'superadmin',
                'no_rumah'       => 'SYSTEM',
            ]
        );

        $this->command->info("✓ Super Admin disinkron — email: superadmin@rt44.com (id={$super->id})");
        if ($envPassword) {
            $this->command->warn("  Password diset sesuai SUPERADMIN_PASSWORD di .env.");
        } else {
            $this->command->warn("  SUPERADMIN_PASSWORD kosong di .env → password acak: {$password}");
            $this->command->warn("  CATAT sekarang, atau isi SUPERADMIN_PASSWORD lalu jalankan ulang seeder.");
        }
    }
}
