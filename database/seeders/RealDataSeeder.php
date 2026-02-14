<?php

namespace Database\Seeders;

use App\Models\Due;
use App\Models\House;
use App\Models\User;
use App\Services\DuesService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RealDataSeeder extends Seeder
{
    /**
     * Seed data rumah & warga dari Data Perumahan.xlsx
     */
    public function run(): void
    {
        // 1. Ensure Admin exists
        User::firstOrCreate(
            ['email' => 'admin@rt44.com'],
            [
                'name' => 'Bendahara RT-44',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'no_rumah' => 'KANTOR',
            ]
        );

        // 2. Clear existing data
        Schema::disableForeignKeyConstraints();
        \App\Models\Payment::truncate();
        \App\Models\Due::truncate();
        \App\Models\House::truncate();
        \App\Models\Expense::truncate();
        \App\Models\MonthlyBalance::truncate();
        User::where('role', 'warga')->delete();
        Schema::enableForeignKeyConstraints();

        // 3. Read data from Excel file
        $excelPath = base_path('Data Perumahan.xlsx');

        if (!file_exists($excelPath)) {
            $this->command->error('File "Data Perumahan.xlsx" tidak ditemukan di root project!');
            return;
        }

        $spreadsheet = IOFactory::load($excelPath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        // Header row 1: Rumah | Pemilik/Penghuni | Kontak | Status Huni | Status Warga
        for ($row = 2; $row <= $highestRow; $row++) {
            $rumah = trim($sheet->getCell('A' . $row)->getValue() ?? '');
            $nama = trim($sheet->getCell('B' . $row)->getValue() ?? '');
            $kontak = trim($sheet->getCell('C' . $row)->getValue() ?? '');
            $statusHuni = trim($sheet->getCell('D' . $row)->getValue() ?? '');
            $statusWarga = trim($sheet->getCell('E' . $row)->getValue() ?? '');

            if (empty($rumah)) {
                continue;
            }

            // Parse blok & nomor dari "G1/2" → blok="G1", nomor="2"
            $parts = explode('/', $rumah, 2);
            $blok = $parts[0];
            $nomor = $parts[1] ?? '';

            $statusHuniDb = strtolower($statusHuni) === 'kosong' ? 'kosong' : 'berpenghuni';

            $residentStatus = 'belum_diketahui';
            $statusWargaLower = strtolower($statusWarga);
            if ($statusWargaLower === 'pemilik') {
                $residentStatus = 'pemilik';
            } elseif ($statusWargaLower === 'kontrak') {
                $residentStatus = 'kontrak';
            }

            // Create user if nama ada
            $owner = null;
            if (!empty($nama)) {
                $owner = User::create([
                    'name' => $nama,
                    'email' => strtolower($blok) . '-' . strtolower($nomor) . '@rt44.com',
                    'password' => Hash::make('password'),
                    'role' => 'warga',
                    'phone_number' => !empty($kontak) ? $kontak : null,
                    'no_rumah' => $rumah,
                ]);
            }

            House::create([
                'blok' => $blok,
                'nomor' => $nomor,
                'status_huni' => $statusHuniDb,
                'resident_status' => $residentStatus,
                'owner_id' => $owner?->id,
            ]);
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        // 4. Generate tagihan untuk Jan s/d Desember (12 bulan penuh)
        $year = now()->year;
        $houses = House::all();

        for ($m = 1; $m <= 12; $m++) {
            $period = Carbon::createFromDate($year, $m, 1);
            $dueDate = $period->copy()->addDays(9); // tanggal 10

            foreach ($houses as $house) {
                $amount = DuesService::calculate($house);

                Due::create([
                    'house_id' => $house->id,
                    'period' => $period->format('Y-m-d'),
                    'amount' => $amount,
                    'status' => 'unpaid',
                    'due_date' => $dueDate->format('Y-m-d'),
                ]);
            }
        }

        // 5. Saldo awal per bulan
        for ($m = 1; $m <= 12; $m++) {
            $period = Carbon::createFromDate($year, $m, 1);
            \App\Models\MonthlyBalance::create([
                'period' => $period->format('Y-m-d'),
                'initial_balance' => 5000000,
                'notes' => 'Saldo awal bulan ' . $period->translatedFormat('F'),
            ]);
        }
    }
}
