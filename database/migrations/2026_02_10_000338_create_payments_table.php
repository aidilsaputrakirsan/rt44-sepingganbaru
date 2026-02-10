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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('due_id')->constrained('dues')->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); // Admin
            $table->foreignId('payer_id')->nullable()->constrained('users')->nullOnDelete(); // Resident
            $table->decimal('amount_paid', 10, 2);
            $table->enum('method', ['transfer', 'cash', 'manual'])->default('manual');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->string('proof_path')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
