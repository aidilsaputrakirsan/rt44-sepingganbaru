<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateDues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dues:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly dues for all houses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $period = \Carbon\Carbon::today()->startOfMonth();
        $dueDate = $period->copy()->addDays(9); // 10th of the month

        $this->info('Generating dues for period: ' . $period->format('Y-m-d'));

        // Check if dues already generated
        if (\App\Models\Due::where('period', $period->format('Y-m-d'))->exists()) {
            $this->warn('Dues already generated for this period.');
            return;
        }

        $houses = \App\Models\House::all();
        $count = 0;

        foreach ($houses as $house) {
            $amount = \App\Services\DuesService::calculate($house);
            
            \App\Models\Due::create([
                'house_id' => $house->id,
                'period' => $period,
                'amount' => $amount,
                'status' => 'unpaid',
                'due_date' => $dueDate,
            ]);
            $count++;
        }

        $this->info("Successfully generated dues for {$count} houses.");
    }
}
