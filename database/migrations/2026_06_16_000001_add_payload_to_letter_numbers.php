<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simpan snapshot data form Surat Pengantar (JSON) supaya PDF bisa
     * di-render ulang / dibuka kembali dari Agenda Surat. Nullable —
     * entri agenda manual (tanpa generator) tidak punya payload.
     */
    public function up(): void
    {
        Schema::table('letter_numbers', function (Blueprint $table) {
            $table->json('payload')->nullable()->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('letter_numbers', function (Blueprint $table) {
            $table->dropColumn('payload');
        });
    }
};
