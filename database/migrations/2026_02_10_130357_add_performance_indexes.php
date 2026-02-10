<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dues', function (Blueprint $table) {
            $table->index('period');
            $table->unique(['house_id', 'period']); // Logical constraint: 1 due per house per period
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('status');
            $table->index(['due_id', 'method']); // For updateOrCreate lookups
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::table('dues', function (Blueprint $table) {
            $table->dropIndex(['period']);
            $table->dropUnique(['house_id', 'period']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['due_id', 'method']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['date']);
        });
    }
};
