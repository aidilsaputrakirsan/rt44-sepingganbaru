<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE houses MODIFY COLUMN resident_status ENUM('pemilik', 'kontrak', 'belum_diketahui') DEFAULT 'belum_diketahui'");
    }

    public function down(): void
    {
        DB::statement("UPDATE houses SET resident_status = 'pemilik' WHERE resident_status = 'belum_diketahui'");
        DB::statement("ALTER TABLE houses MODIFY COLUMN resident_status ENUM('pemilik', 'kontrak') DEFAULT 'pemilik'");
    }
};
