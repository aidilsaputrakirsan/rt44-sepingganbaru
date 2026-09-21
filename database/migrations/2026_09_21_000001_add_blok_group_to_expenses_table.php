<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penanda blok untuk tiap pengeluaran:
     * - 'G'      : murni kebutuhan blok G
     * - 'H'      : murni kebutuhan blok H
     * - 'shared' : melayani dua blok, dibagi proporsional jumlah rumah saat pelaporan
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('blok_group', 10)->default('shared')->after('category');
            $table->index('blok_group');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['blok_group']);
            $table->dropColumn('blok_group');
        });
    }
};
