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
        $year = \Carbon\Carbon::today()->year;
        $houses = \App\Models\House::where('is_subsidized', false)->get();
        $totalCreated = 0;

        for ($m = 1; $m <= 12; $m++) {
            $period = \Carbon\Carbon::createFromDate($year, $m, 1);
            $dueDate = $period->copy()->addDays(9); // 10th of the month

            // Skip if dues already generated for this period
            if (\App\Models\Due::where('period', $period->format('Y-m-d'))->exists()) {
                continue;
            }

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

            $this->info("Generated {$count} dues for " . $period->format('F Y'));
            $totalCreated += $count;
        }

        if ($totalCreated === 0) {
            $this->info("All months for {$year} already generated.");
        } else {
            $this->info("Total: {$totalCreated} dues created.");
        }
    }
}
