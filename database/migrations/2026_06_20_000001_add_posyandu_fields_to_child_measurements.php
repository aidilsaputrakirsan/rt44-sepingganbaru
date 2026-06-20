<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lengkapi child_measurements dengan field laporan Posyandu (mengikuti
     * format laporan kader PKK): LILA, lingkar kepala, vitamin A, ASI eksklusif, PMT.
     * berat_kg & tinggi_cm dijadikan nullable karena di lapangan ada anak yang
     * gagal diukur sebagian (mis. rewel/tantrum).
     */
    public function up(): void
    {
        Schema::table('child_measurements', function (Blueprint $table) {
            $table->decimal('berat_kg', 5, 2)->nullable()->change();
            $table->decimal('tinggi_cm', 5, 1)->nullable()->change();

            $table->decimal('lila_cm', 4, 1)->nullable()->after('tinggi_cm');            // lingkar lengan atas
            $table->decimal('lingkar_kepala_cm', 4, 1)->nullable()->after('lila_cm');     // lingkar kepala
            $table->boolean('vitamin_a')->nullable()->after('lingkar_kepala_cm');         // dapat kapsul vit A?
            $table->json('asi_eksklusif')->nullable()->after('vitamin_a');                // daftar bulan ASI eksklusif [0..6]
            $table->unsignedSmallInteger('pmt_ke')->nullable()->after('asi_eksklusif');   // pemberian PMT ke-
            $table->string('pmt_sumber')->nullable()->after('pmt_ke');                    // sumber PMT (pusat/daerah/dll)
        });
    }

    public function down(): void
    {
        Schema::table('child_measurements', function (Blueprint $table) {
            $table->dropColumn(['lila_cm', 'lingkar_kepala_cm', 'vitamin_a', 'asi_eksklusif', 'pmt_ke', 'pmt_sumber']);
            $table->decimal('berat_kg', 5, 2)->nullable(false)->change();
            $table->decimal('tinggi_cm', 5, 1)->nullable(false)->change();
        });
    }
};
