<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $orphans = DB::table('users')
            ->leftJoin('houses', 'houses.owner_id', '=', 'users.id')
            ->where('users.role', 'warga')
            ->whereNull('houses.id')
            ->select('users.id', 'users.no_rumah', 'users.email')
            ->get();

        foreach ($orphans as $orphan) {
            $validUser = DB::table('users')
                ->join('houses', 'houses.owner_id', '=', 'users.id')
                ->where('users.role', 'warga')
                ->where('users.no_rumah', $orphan->no_rumah)
                ->where('users.id', '!=', $orphan->id)
                ->select('users.id')
                ->first();

            if (!$validUser) {
                continue;
            }

            DB::table('payments')
                ->where('payer_id', $orphan->id)
                ->update(['payer_id' => $validUser->id]);

            DB::table('users')->where('id', $orphan->id)->delete();
        }
    }

    public function down(): void
    {
    }
};
