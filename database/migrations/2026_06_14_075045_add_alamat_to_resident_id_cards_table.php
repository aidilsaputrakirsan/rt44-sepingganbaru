<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('resident_id_cards', function (Blueprint $table) {
            // Alamat sesuai KTP (bisa beda dgn alamat rumah saat ini)
            $table->string('alamat', 255)->nullable()->after('kewarganegaraan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resident_id_cards', function (Blueprint $table) {
            $table->dropColumn('alamat');
        });
    }
};
