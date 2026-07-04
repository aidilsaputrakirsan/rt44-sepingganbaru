<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('jumlah_mobil')->default(0);
            $table->unsignedTinyInteger('jumlah_motor')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_counts');
    }
};
