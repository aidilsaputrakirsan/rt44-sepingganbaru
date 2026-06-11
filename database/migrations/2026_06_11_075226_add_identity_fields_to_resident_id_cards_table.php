<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah field identitas (semua opsional) ke resident_id_cards.
     * Tiap baris resident_id_cards = 1 anggota keluarga; identitas ini
     * dipakai ulang untuk auto-fill Surat Pengantar.
     * file_path dibuat nullable supaya bisa daftar anggota tanpa scan KTP.
     */
    public function up(): void
    {
        Schema::table('resident_id_cards', function (Blueprint $table) {
            $table->string('nama')->nullable()->after('label');
            $table->string('jenis_kelamin', 20)->nullable()->after('nomor_ktp');
            $table->string('tempat_lahir')->nullable()->after('jenis_kelamin');
            $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            $table->string('status_perkawinan', 30)->nullable()->after('tanggal_lahir');
            $table->string('agama', 30)->nullable()->after('status_perkawinan');
            $table->string('pekerjaan')->nullable()->after('agama');
            $table->string('golongan_darah', 5)->nullable()->after('pekerjaan');
            $table->string('kewarganegaraan', 30)->nullable()->after('golongan_darah');
            $table->string('file_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('resident_id_cards', function (Blueprint $table) {
            $table->dropColumn([
                'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
                'status_perkawinan', 'agama', 'pekerjaan', 'golongan_darah', 'kewarganegaraan',
            ]);
            $table->string('file_path')->nullable(false)->change();
        });
    }
};
