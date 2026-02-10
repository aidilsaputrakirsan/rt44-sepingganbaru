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
        Schema::create('houses', function (Blueprint $table) {
            $table->id();
            $table->string('blok'); // G or H
            $table->string('nomor'); // 1, 2, ...
            $table->enum('status_huni', ['berpenghuni', 'kosong'])->default('berpenghuni');
            $table->boolean('is_connected')->default(false); // 2 rumah tersambung
            $table->integer('meteran_count')->default(1); // 1 or 2
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('houses');
    }
};
