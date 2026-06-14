<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah role 'superadmin' (pengelola akun admin & ketua) + kolom password_plain.
     *
     * password_plain menyimpan salinan password ter-ENKRIPSI (Crypt, reversible via APP_KEY)
     * supaya super admin bisa "mengintip" password yang berlaku. Hanya diisi untuk akun
     * admin/ketua yang passwordnya di-set lewat panel super admin. Tidak dipakai untuk login
     * (login tetap pakai kolom `password` yang di-hash bcrypt).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'admin', 'ketua', 'warga', 'demo') NOT NULL DEFAULT 'warga'");

        Schema::table('users', function (Blueprint $table) {
            $table->text('password_plain')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_plain');
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'ketua', 'warga', 'demo') NOT NULL DEFAULT 'warga'");
    }
};
