<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->date('payment_date')->nullable()->after('verified_at');
        });

        // Backfill: use verified_at date if available, otherwise created_at
        DB::statement("
            UPDATE payments
            SET payment_date = COALESCE(DATE(verified_at), DATE(created_at))
            WHERE payment_date IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_date');
        });
    }
};
