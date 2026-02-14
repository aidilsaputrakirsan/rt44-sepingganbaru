<?php

namespace App\Console\Commands;

use App\Models\Due;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateOverdueDues extends Command
{
    protected $signature = 'dues:update-overdue';

    protected $description = 'Set status overdue untuk tagihan yang sudah lewat jatuh tempo dan belum lunas';

    public function handle()
    {
        $today = Carbon::today();

        $updated = Due::where('status', 'unpaid')
            ->where('due_date', '<', $today)
            ->update(['status' => 'overdue']);

        $this->info("Updated {$updated} dues to overdue status.");
    }
}
