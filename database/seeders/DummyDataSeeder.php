<?php

namespace Database\Seeders;

use App\Models\Due;
use App\Models\House;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Admin Exists
        $admin = User::firstOrCreate(
            ['email' => 'admin@rt44.com'],
            [
                'name' => 'Bendahara RT-44',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'no_rumah' => 'KANTOR',
            ]
        );

        // 2. Clear existing data to avoid duplicates/conflicts if re-run
        Schema::disableForeignKeyConstraints();
        \App\Models\Payment::truncate();
        \App\Models\Due::truncate();
        \App\Models\House::truncate();
        \App\Models\Expense::truncate();
        \App\Models\MonthlyBalance::truncate();
        User::where('role', 'warga')->delete();
        Schema::enableForeignKeyConstraints();

        $faker = \Faker\Factory::create('id_ID');

        // 3. Create Residents & Houses
        $blocks = ['G1', 'G2', 'G3', 'H1', 'H2'];
        $housesArr = [];

        foreach ($blocks as $block) {
            for ($i = 1; $i <= 8; $i++) {
                // Randomly decide house status
                $statusHuni = $faker->randomElement(['berpenghuni', 'berpenghuni', 'berpenghuni', 'kosong']); // 75% occupied

                $owner = null;
                if ($statusHuni === 'berpenghuni') {
                    $owner = User::create([
                        'name' => $faker->name,
                        'email' => strtolower($block . $i) . '@rt44.com',
                        'password' => Hash::make('password'),
                        'role' => 'warga',
                        'phone_number' => $faker->phoneNumber,
                        'no_rumah' => "$block/$i",
                    ]);
                }

                $house = House::create([
                    'blok' => $block,
                    'nomor' => (string) $i,
                    'status_huni' => $statusHuni,
                    'owner_id' => $owner ? $owner->id : null,
                ]);

                $housesArr[] = $house;
            }
        }

        // 4. Create Special Cases
        // Case: Connected Houses (Sultan) - G1/1 & G1/2
        $sultan = User::create([
            'name' => 'Sultan Andara',
            'email' => 'sultan@rt44.com',
            'password' => Hash::make('password'),
            'role' => 'warga',
            'no_rumah' => 'G1/1-2',
        ]);

        // Update first two houses to be owned by Sultan and connected
        $h1 = House::where('blok', 'G1')->where('nomor', '1')->first();
        $h2 = House::where('blok', 'G1')->where('nomor', '2')->first();

        $h1->update(['owner_id' => $sultan->id, 'status_huni' => 'berpenghuni', 'is_connected' => true, 'meteran_count' => 1]);
        $h2->update(['owner_id' => $sultan->id, 'status_huni' => 'berpenghuni', 'is_connected' => true, 'meteran_count' => 1]);


        // 5. Generate Dues & Payments for Current Year
        $year = now()->year;
        $currentMonth = now()->month;

        foreach ($housesArr as $house) {
            $house->refresh();

            for ($m = 1; $m <= 12; $m++) {
                $date = Carbon::createFromDate($year, $m, 1);

                // Determine Amount (Wajib Portion)
                $amount = ($house->status_huni === 'berpenghuni') ? 160000 : 110000;

                // Adjustment for special sultan case (135k logic)
                if ($house->is_connected && $house->meteran_count == 1) {
                    $amount = 135000;
                }

                $due = Due::create([
                    'house_id' => $house->id,
                    'period' => $date->format('Y-m-d'),
                    'amount' => $amount,
                    'due_date' => $date->copy()->endOfMonth(),
                    'status' => 'unpaid',
                ]);

                if ($m < $currentMonth) {
                    $rand = rand(1, 100);
                    if ($rand <= 80) { // Paid On Time + some excess
                        $paidAmount = $amount + (rand(0, 5) * 10000); // Sometimes pay more
                        $this->payDue($due, $paidAmount, $date->copy()->addDays(rand(1, 20)));
                    } elseif ($rand <= 90) { // Partial
                        $this->payDue($due, $amount / 2, $date->copy()->addDays(rand(1, 20)));
                    }
                } elseif ($m === $currentMonth) {
                    if (rand(1, 100) <= 50) {
                        $this->payDue($due, $amount, $date->copy()->addDays(rand(1, 5)));
                    }
                }
            }
        }

        // 6. Create Dummy Expenses for current and previous months
        for ($m = 1; $m <= $currentMonth; $m++) {
            $date = Carbon::createFromDate($year, $m, 10);

            \App\Models\Expense::create([
                'title' => 'Gaji Security - ' . $date->format('F'),
                'amount' => 1500000,
                'category' => 'operasional',
                'date' => $date->format('Y-m-d'),
            ]);

            \App\Models\Expense::create([
                'title' => 'Biaya Sampah & Kebersihan',
                'amount' => 500000,
                'category' => 'operasional',
                'date' => $date->copy()->addDays(5)->format('Y-m-d'),
            ]);

            if ($m % 3 == 0) {
                \App\Models\Expense::create([
                    'title' => 'Perbaikan Lampu Jalan Blok G',
                    'amount' => 350000,
                    'category' => 'perbaikan',
                    'date' => $date->copy()->addDays(12)->format('Y-m-d'),
                ]);
            }

            // 7. Set Monthly Initial Balances (Anchor)
            \App\Models\MonthlyBalance::create([
                'period' => Carbon::createFromDate($year, $m, 1)->format('Y-m-d'),
                'initial_balance' => 5000000, // Fixed start for dummy
                'notes' => 'Saldo awal bulan ' . $date->format('F'),
            ]);
        }
    }

    private function payDue($due, $amount, $date)
    {
        $house = $due->house;
        $wajibAmount = ($house->status_huni === 'berpenghuni') ? 160000 : 110000;
        if ($house->is_connected && $house->meteran_count == 1)
            $wajibAmount = 135000;

        $amountWajib = min($amount, $wajibAmount);
        $amountSukarela = max(0, $amount - $wajibAmount);

        Payment::create([
            'due_id' => $due->id,
            'payer_id' => $house->owner_id,
            'recorded_by' => 1,
            'amount_paid' => $amount,
            'amount_wajib' => $amountWajib,
            'amount_sukarela' => $amountSukarela,
            'method' => 'manual',
            'status' => 'verified',
            'verified_at' => $date,
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        $totalPaidWajib = $due->payments()->where('status', 'verified')->sum('amount_wajib');
        if ($totalPaidWajib >= $due->amount) {
            $due->update(['status' => 'paid']);
        }
    }
}
