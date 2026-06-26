<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->date('period')->unique(); // YYYY-MM-01
            $table->date('tanggal_pengesahan')->nullable();
            $table->string('lurah_name')->default('SARBIN');
            $table->string('ketua_name')->default('AIDIL SAPUTRA KIRSAN');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('monthly_report_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_report_id')->constrained('monthly_reports')->cascadeOnDelete();
            $table->date('tanggal');
            $table->text('uraian');
            $table->string('no_surat')->nullable();       // teks "36/RT.44/V/2026" dari Agenda Surat
            $table->string('photo_path')->nullable();      // path di storage public disk
            $table->string('photo_orientation')->nullable(); // landscape | portrait
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_report_activities');
        Schema::dropIfExists('monthly_reports');
    }
};
