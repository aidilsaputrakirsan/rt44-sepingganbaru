<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('no_rumah');
        });

        Schema::table('resident_profiles', function (Blueprint $table) {
            $table->string('nomor_kk', 32)->nullable()->after('jumlah_anggota_keluarga');
        });

        Schema::table('resident_id_cards', function (Blueprint $table) {
            $table->string('nomor_ktp', 32)->nullable()->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });

        Schema::table('resident_profiles', function (Blueprint $table) {
            $table->dropColumn('nomor_kk');
        });

        Schema::table('resident_id_cards', function (Blueprint $table) {
            $table->dropColumn('nomor_ktp');
        });
    }
};
