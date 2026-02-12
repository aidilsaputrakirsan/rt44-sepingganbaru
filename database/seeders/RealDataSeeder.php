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
use Illuminate\Support\Str;

class RealDataSeeder extends Seeder
{
    /**
     * Seed data rumah & warga dari Data Perumahan.xlsx
     * Data apa adanya, kolom Kontak kosong semua.
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

        // 3. Data dari Excel "Data Perumahan.xlsx"
        // Format: [rumah, nama, status_huni, status_warga]
        $data = [
            ['G1/1', '', 'Berpenghuni', ''],
            ['G1/2', 'HANS KARTONO', 'Berpenghuni', 'Pemilik'],
            ['G1/3', '', 'Kosong', ''],
            ['G1/5', 'DEDI SUNARDI', 'Berpenghuni', 'Pemilik'],
            ['G1/6', 'ABIYU', 'Berpenghuni', 'Kontrak'],
            ['G1/7', 'BONDAN GURYO', 'Berpenghuni', 'Pemilik'],
            ['G1/8', 'SAMUEL BANUERA', 'Berpenghuni', 'Pemilik'],
            ['G1/9', 'SAUT SIMANJUNTAK', 'Berpenghuni', 'Pemilik'],
            ['G1/10', 'MARTIN', 'Berpenghuni', 'Kontrak'],
            ['G1/11', 'FIRMANSYAH', 'Berpenghuni', 'Pemilik'],
            ['G1/12', 'RIRIN.S', 'Berpenghuni', 'Pemilik'],
            ['G2/1', 'WAHYU HANDIKA', 'Berpenghuni', 'Kontrak'],
            ['G2/2', 'ANDI WALIUDIN', 'Berpenghuni', 'Pemilik'],
            ['G2/3', 'RONY LOKA JAYA', 'Berpenghuni', 'Pemilik'],
            ['G2/5', 'SUPIYAH / JUNAIDI', 'Berpenghuni', 'Pemilik'],
            ['G2/6', 'RIFKI RAMADHAN / ELAN METANA', 'Berpenghuni', 'Pemilik'],
            ['G2/7', 'SAHAR', 'Berpenghuni', 'Pemilik'],
            ['G2/8', 'ROBERT KRISTIAN EFFENDHI', 'Berpenghuni', 'Pemilik'],
            ['G2/9', 'DONNY SAMBANG', 'Berpenghuni', 'Pemilik'],
            ['G2/10', 'DIOS ANDRI', 'Berpenghuni', 'Pemilik'],
            ['G2/11', 'M. KURNIADI SALAM', 'Berpenghuni', 'Kontrak'],
            ['G2/12', 'MINTER TAMBA', 'Berpenghuni', 'Pemilik'],
            ['G3/1', 'FITRIYANI', 'Berpenghuni', 'Pemilik'],
            ['G3/2', '', 'Berpenghuni', ''],
            ['G3/3', 'FREDI WIKARNO', 'Berpenghuni', 'Pemilik'],
            ['G3/5', 'ADE JASMAN', 'Berpenghuni', 'Kontrak'],
            ['G3/6', 'KEMIS', 'Berpenghuni', 'Pemilik'],
            ['G3/7', 'WIDODO', 'Berpenghuni', 'Pemilik'],
            ['G4/1', 'IVONI ABIDIN', 'Berpenghuni', 'Pemilik'],
            ['G4/2', 'SUWONDO', 'Berpenghuni', 'Pemilik'],
            ['G4/3', 'HENDRI KUSWANTO', 'Berpenghuni', 'Pemilik'],
            ['G4/5', 'SUDARYONO', 'Berpenghuni', 'Pemilik'],
            ['G4/6', 'SUTRISNO', 'Berpenghuni', 'Pemilik'],
            ['G4/7', 'MARSIUG FERDINAN', 'Berpenghuni', 'Pemilik'],
            ['G5/1', 'DR. GHULAM ISKANDAR', 'Kosong', 'Pemilik'],
            ['G5/2', '', 'Berpenghuni', ''],
            ['G5/3', 'SYAFRIN BUANA HARAHAP', 'Berpenghuni', 'Pemilik'],
            ['G6/1', 'EKO HERU SUGIARTO', 'Berpenghuni', 'Pemilik'],
            ['G6/2', 'DEWANGGA ISKANDAR', 'Berpenghuni', 'Kontrak'],
            ['G6/3', 'SAMUDERI PUTRA', 'Berpenghuni', 'Pemilik'],
            ['G6/5', '', 'Berpenghuni', ''],
            ['G6/6', 'REYZANDI', 'Berpenghuni', 'Pemilik'],
            ['G6/7', 'ARI SUDEWO', 'Berpenghuni', 'Pemilik'],
            ['G6/8', '', 'Kosong', ''],
            ['G6/9', 'A. AGUSRIANSYAH', 'Berpenghuni', 'Pemilik'],
            ['G7/1', '', 'Berpenghuni', ''],
            ['G7/2', 'M. HUSIN', 'Berpenghuni', 'Pemilik'],
            ['G7/3', 'HERLAMBANG', 'Berpenghuni', 'Kontrak'],
            ['G7/5', '', 'Berpenghuni', ''],
            ['G7/6', 'ARI FIANDI FADILAH', 'Berpenghuni', 'Pemilik'],
            ['G7/7', 'BAGRI', 'Berpenghuni', 'Pemilik'],
            ['G7/8', 'DEDI JUNAIDI', 'Berpenghuni', 'Pemilik'],
            ['G7/9', 'ARIEF PURNAWARMAN', 'Berpenghuni', 'Pemilik'],
            ['G7/10', 'L. AMIR MURTONO', 'Berpenghuni', 'Kontrak'],
            ['G7/11', 'GAGANG RIMBA BORNEO', 'Berpenghuni', 'Kontrak'],
            ['G7/12', '', 'Berpenghuni', ''],
            ['G7/15', '', 'Kosong', ''],
            ['G7/16', '', 'Kosong', ''],
            ['G7/17', '', 'Kosong', ''],
            ['G7/18', 'EKA JUNAIKA', 'Kosong', 'Pemilik'],
            ['G7/19', 'ABDUL RAHMAN', 'Berpenghuni', 'Pemilik'],
            ['G8/1', 'BAMBANG SUPRIJANTORO', 'Berpenghuni', 'Pemilik'],
            ['G8/2', 'M. ALI YUSRON', 'Berpenghuni', 'Kontrak'],
            ['G9/1', 'AGUS SUPRAPTO', 'Berpenghuni', 'Kontrak'],
            ['G9/2', '', 'Berpenghuni', ''],
            ['G9/3', 'RIYAN DARMAWAN', 'Berpenghuni', 'Pemilik'],
            ['G9/5', 'IRKHAM IMAM TANTOWI', 'Berpenghuni', 'Kontrak'],
            ['G9/6', 'JIMMY ADITYA NUGRAHA', 'Berpenghuni', 'Pemilik'],
            ['G10/1', 'GUNTUR DARJA WIJAYA', 'Berpenghuni', 'Pemilik'],
            ['G10/2', '', 'Kosong', ''],
            ['G10/3', 'LIDIJA SIBURIAN', 'Berpenghuni', 'Pemilik'],
            ['G10/5', 'ADHI SUHERMAN', 'Berpenghuni', 'Pemilik'],
            ['G10/6', 'HENDAR SUNANDAR', 'Berpenghuni', 'Pemilik'],
            ['G10/7', 'FACHRU RAZI', 'Berpenghuni', 'Pemilik'],
            ['G10/8', 'ASHARI', 'Berpenghuni', 'Pemilik'],
            ['G10/9', 'SISWANTO', 'Berpenghuni', 'Pemilik'],
            ['G10/10', '', 'Berpenghuni', ''],
            ['G10/11', '', 'Berpenghuni', ''],
            ['G11/1', '', 'Berpenghuni', ''],
            ['G11/2', 'ATQIYA RAMADHAN / LILIS PARAMITHA', 'Berpenghuni', 'Pemilik'],
            ['G11/3', '', 'Berpenghuni', ''],
            ['G11/5', 'M. RUSDI FEBRIO', 'Berpenghuni', 'Pemilik'],
            ['G11/6', '', 'Berpenghuni', ''],
            ['G11/7', 'DODHY PURDWIANTO', 'Berpenghuni', 'Pemilik'],
            ['G11/8', 'SUNYOTO', 'Berpenghuni', 'Pemilik'],
            ['G11/9', 'RAHMAWATI', 'Berpenghuni', 'Pemilik'],
            ['G12/1', 'NADYA', 'Berpenghuni', 'Kontrak'],
            ['G12/2', 'EROS ROHANA', 'Berpenghuni', 'Pemilik'],
            ['G12/3', 'M. ERWIN', 'Berpenghuni', 'Pemilik'],
            ['G12/5', 'NATA', 'Berpenghuni', 'Kontrak'],
            ['G12/6', 'NOVIE NORTHON (MAMA IYOS)', 'Berpenghuni', 'Pemilik'],
            ['G12/7', 'HARISDIANTO CAHYADI', 'Berpenghuni', 'Pemilik'],
            ['G12/8', 'MERNAWATI / ARKAS RAMILER', 'Berpenghuni', 'Pemilik'],
            ['G12/9', 'AIDIL SAPUTRA K', 'Berpenghuni', 'Pemilik'],
            ['G12/10', '', 'Kosong', ''],
            ['G12/11', '', 'Kosong', ''],
            ['G13/1', '', 'Kosong', ''],
            ['G13/2', 'WALDI FITRIYANTO', 'Berpenghuni', 'Pemilik'],
            ['G13/3', '', 'Berpenghuni', ''],
            ['G13/4', 'ANDRY KURNIAWAN', 'Berpenghuni', 'Pemilik'],
            ['G13/5', 'YUDHI HERMAWAN', 'Berpenghuni', 'Pemilik'],
            ['G13/6', '', 'Berpenghuni', ''],
            ['G13/7', '', 'Berpenghuni', ''],
            ['G13/8', 'MARIA RAPA', 'Berpenghuni', 'Kontrak'],
            ['G13/9', '', 'Berpenghuni', ''],
            ['G13/10', 'DHERY DIAN OKTARIANTO', 'Berpenghuni', 'Pemilik'],
            ['G13/11', '', 'Berpenghuni', ''],
            ['G13/12', '', 'Berpenghuni', ''],
            ['G13/15', 'H. DARMA WAHYUDI', 'Berpenghuni', 'Pemilik'],
            ['G14/1', 'DR. ERWIN BASKORO', 'Berpenghuni', 'Pemilik'],
            ['G14/2', 'GATOT', 'Berpenghuni', 'Pemilik'],
            ['G14/3', 'HAMI', 'Berpenghuni', 'Pemilik'],
            ['G14/5', 'SLAMET', 'Berpenghuni', 'Pemilik'],
            ['G14/6', '', 'Berpenghuni', ''],
            ['G14/7', 'ATIN SUPRIATIN', 'Berpenghuni', 'Pemilik'],
            ['G14/8', 'M. ERWAN ZAMZURI', 'Berpenghuni', 'Pemilik'],
            ['G14/9', '', 'Berpenghuni', ''],
            ['G14/10', '', 'Berpenghuni', ''],
            ['H/1', '', 'Berpenghuni', ''],
            ['H1/1', 'NATAN. ST / ORLANDO YALENTIN', 'Berpenghuni', 'Pemilik'],
            ['H1/2', 'ARIE HENDRO WIBOWO', 'Berpenghuni', 'Pemilik'],
            ['H1/5', '', 'Berpenghuni', ''],
            ['H1/6', 'SETYAWAN BOEDI', 'Berpenghuni', 'Pemilik'],
            ['H2/2', '', 'Berpenghuni', ''],
            ['H2/3', '', 'Berpenghuni', ''],
            ['H2/6', '', 'Kosong', ''],
            ['H2/7', '', 'Berpenghuni', ''],
            ['H3/1-2', '', 'Berpenghuni', ''],
            ['H3/3', '', 'Berpenghuni', ''],
            ['H3/8', '', 'Berpenghuni', ''],
            ['H3/9', '', 'Berpenghuni', ''],
            ['H3/10', '', 'Berpenghuni', ''],
            ['H3/11', '', 'Berpenghuni', ''],
            ['H3/12', '', 'Berpenghuni', ''],
            ['H5/1', 'BAYU TOPALGUNA', 'Berpenghuni', 'Pemilik'],
            ['H5/2', '', 'Berpenghuni', ''],
            ['H5/3', 'M. FIKRI TULUS', 'Berpenghuni', 'Pemilik'],
            ['H5/5', 'IWAN MANGIRI', 'Berpenghuni', 'Pemilik'],
            ['H5/6', 'R. JASMAN ADITYA', 'Berpenghuni', 'Pemilik'],
            ['H7/1', '', 'Berpenghuni', ''],
            ['H7/3', '', 'Berpenghuni', ''],
            ['H7/5', '', 'Berpenghuni', ''],
        ];

        foreach ($data as $row) {
            [$rumah, $nama, $statusHuni, $statusWarga] = $row;

            // Parse blok & nomor dari "G1/2" → blok="G1", nomor="2"
            // Handle special case "H/1" → blok="H", nomor="1"
            // Handle special case "H3/1-2" → blok="H3", nomor="1-2"
            $parts = explode('/', $rumah, 2);
            $blok = $parts[0];
            $nomor = $parts[1] ?? '';

            $statusHuniDb = strtolower($statusHuni) === 'kosong' ? 'kosong' : 'berpenghuni';

            // Map status warga
            $residentStatus = 'pemilik'; // default
            if (strtolower($statusWarga) === 'kontrak') {
                $residentStatus = 'kontrak';
            }

            // Create user if nama ada
            $owner = null;
            if (!empty(trim($nama))) {
                $emailSlug = strtolower($blok) . '.' . strtolower(str_replace('-', '', $nomor));
                $owner = User::create([
                    'name' => $nama,
                    'email' => $emailSlug . '@rt44.com',
                    'password' => Hash::make('password'),
                    'role' => 'warga',
                    'phone_number' => null,
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
