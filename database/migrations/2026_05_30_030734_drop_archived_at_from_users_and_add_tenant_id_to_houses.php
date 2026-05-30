<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Pulihkan email user yg sempat di-archive (jika ada) supaya konsisten,
        //    karena fitur archive di-rollback total. Skip kalau kolom belum ada.
        if (Schema::hasColumn('users', 'archived_at')) {
            DB::table('users')
                ->whereNotNull('archived_at')
                ->where('email', 'LIKE', 'archived_%')
                ->get(['id', 'email'])
                ->each(function ($u) {
                    $clean = preg_replace('/^archived_\d+_/', '', $u->email);
                    if ($clean !== $u->email && !DB::table('users')->where('email', $clean)->exists()) {
                        DB::table('users')->where('id', $u->id)->update(['email' => $clean]);
                    }
                });

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('archived_at');
            });
        }

        if (!Schema::hasColumn('houses', 'tenant_id')) {
            Schema::table('houses', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('owner_id')
                    ->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('no_rumah');
        });
    }
};
