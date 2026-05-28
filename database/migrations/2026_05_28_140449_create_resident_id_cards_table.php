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
        Schema::create('resident_id_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_profile_id')->constrained('resident_profiles')->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('file_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resident_id_cards');
    }
};
