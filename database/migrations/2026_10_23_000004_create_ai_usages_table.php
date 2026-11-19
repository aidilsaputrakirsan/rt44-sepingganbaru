<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pemakaian AI (OpenRouter) per panggilan: token & biaya, untuk dicocokkan dengan tagihan.
 * Tidak menyimpan isi pertanyaan/jawaban AI (isi pertanyaan ada di tanya_data_logs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('fitur', 40);
            $table->string('model');
            $table->string('provider')->nullable();         // penyedia yang dipilih OpenRouter (selalu ZDR)
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->decimal('biaya_usd', 12, 6)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usages');
    }
};
