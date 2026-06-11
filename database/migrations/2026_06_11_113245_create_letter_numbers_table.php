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
        Schema::create('letter_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('nomor_urut');           // urutan dalam 1 tahun
            $table->unsignedSmallInteger('tahun');           // tahun terbit
            $table->unsignedTinyInteger('bulan');            // bulan terbit (1-12) utk Romawi
            $table->string('jenis')->default('Surat Pengantar'); // jenis surat
            $table->string('keterangan')->nullable();        // peruntukan / nama pemohon
            $table->date('tanggal');                         // tanggal terbit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Cegah nomor urut ganda dalam tahun yang sama
            $table->unique(['tahun', 'nomor_urut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_numbers');
    }
};
