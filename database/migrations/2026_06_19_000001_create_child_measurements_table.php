<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengukuran antropometri balita (untuk pemantauan stunting/gizi).
     * Tiap baris = 1 kali pengukuran pada 1 anggota (resident_id_cards).
     * Umur saat ukur dihitung dari tanggal_lahir vs tanggal_ukur.
     */
    public function up(): void
    {
        Schema::create('child_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id_card_id')->constrained('resident_id_cards')->cascadeOnDelete();
            $table->date('tanggal_ukur');
            $table->decimal('berat_kg', 5, 2);     // 0.00 - 999.99
            $table->decimal('tinggi_cm', 5, 1);    // 0.0 - 9999.9
            $table->enum('cara_ukur', ['berdiri', 'terlentang'])->default('berdiri'); // koreksi 0.7cm WHO
            $table->string('catatan')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['resident_id_card_id', 'tanggal_ukur']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_measurements');
    }
};
