<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class WargaController extends Controller
{
    public function index()
    {
        $houses = House::with('owner')
            ->orderByRaw("REGEXP_SUBSTR(blok, '^[A-Za-z]+') ASC")
            ->orderByRaw("CAST(REGEXP_SUBSTR(blok, '[0-9]+') AS UNSIGNED) ASC")
            ->orderByRaw('CAST(nomor AS UNSIGNED) ASC')
            ->orderBy('nomor')
            ->get();

        if (auth()->user()->role === 'demo') {
            $houses->each(function ($house) {
                if ($house->owner) {
                    $house->owner->name = $this->censorName($house->owner->name);
                    $house->owner->email = $this->censorEmail($house->owner->email);
                    $house->owner->phone_number = $this->censorPhone($house->owner->phone_number);
                }
            });
        }

        return Inertia::render('Admin/Warga/Index', [
            'houses' => $houses
        ]);
    }

    private function censorName(?string $name): string
    {
        if (!$name) return '-';
        return preg_replace_callback('/\b(\w)(\w+)\b/u', function ($m) {
            return $m[1] . str_repeat('*', mb_strlen($m[2]));
        }, $name);
    }

    private function censorEmail(?string $email): string
    {
        if (!$email) return '-';
        $parts = explode('@', $email);
        $local = $parts[0];
        $domain = $parts[1] ?? 'rt44.com';
        $visible = mb_substr($local, 0, 2);
        return $visible . str_repeat('*', max(3, mb_strlen($local) - 2)) . '@' . $domain;
    }

    private function censorPhone(?string $phone): ?string
    {
        if (!$phone) return null;
        $len = strlen($phone);
        if ($len <= 4) return str_repeat('*', $len);
        return substr($phone, 0, 4) . str_repeat('*', $len - 4);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'blok' => 'required|string',
            'nomor' => 'required|string',
            'status_huni' => 'required|in:berpenghuni,kosong',
            'resident_status' => 'required|in:pemilik,kontrak,belum_diketahui',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone_number' => 'nullable|string',
            'is_subsidized' => 'required|boolean',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'] ?? 'Warga ' . $validated['blok'] . '/' . $validated['nomor'],
                'email' => $validated['email'] ?? strtolower($validated['blok']) . '-' . strtolower($validated['nomor']) . '@rt44.com',
                'password' => Hash::make('password'),
                'role' => 'warga',
                'phone_number' => $validated['phone_number'],
                'no_rumah' => $validated['blok'] . '/' . $validated['nomor'],
            ]);

            $house = House::create([
                'blok' => $validated['blok'],
                'nomor' => $validated['nomor'],
                'status_huni' => $validated['status_huni'],
                'resident_status' => $validated['resident_status'],
                'is_subsidized' => $validated['is_subsidized'],
                'owner_id' => $user->id,
            ]);

            if (!$house->is_subsidized) {
                // Create dues for all 12 months of the current year
                $year = now()->year;
                $amount = ($house->status_huni === 'berpenghuni') ? 160000 : 110000;

                for ($m = 1; $m <= 12; $m++) {
                    $period = \Carbon\Carbon::create($year, $m, 1)->format('Y-m-d');
                    $dueDate = \Carbon\Carbon::create($year, $m, 1)->endOfMonth()->format('Y-m-d');

                    \App\Models\Due::create([
                        'house_id' => $house->id,
                        'period' => $period,
                        'amount' => $amount,
                        'due_date' => $dueDate,
                        'status' => 'unpaid'
                    ]);
                }
            }
        });

        return back()->with('success', 'Data warga berhasil ditambahkan.');
    }

    public function update(Request $request, House $house)
    {
        $validated = $request->validate([
            'status_huni' => 'required|in:berpenghuni,kosong',
            'resident_status' => 'required|in:pemilik,kontrak,belum_diketahui',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . ($house->owner_id ?? 0),
            'phone_number' => 'nullable|string',
            'is_subsidized' => 'required|boolean',
        ]);

        DB::transaction(function () use ($validated, $house) {
            if ($house->owner_id) {
                $house->owner->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone_number' => $validated['phone_number'],
                ]);
            } else {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make('password'),
                    'role' => 'warga',
                    'phone_number' => $validated['phone_number'],
                    'no_rumah' => $house->blok . '/' . $house->nomor,
                ]);
                $house->owner_id = $user->id;
            }

            $house->status_huni = $validated['status_huni'];
            $house->resident_status = $validated['resident_status'];
            $house->is_subsidized = $validated['is_subsidized'];
            $house->save();

            $currentPeriod = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');

            if ($house->is_subsidized) {
                // Delete ALL unpaid/overdue dues since house is now subsidized (past, current, future)
                \App\Models\Due::where('house_id', $house->id)
                    ->whereIn('status', ['unpaid', 'overdue'])
                    ->delete();
            } else {
                // Auto-update current & future months' bill amount based on new status
                $newAmount = \App\Services\DuesService::calculate($house);
                \App\Models\Due::where('house_id', $house->id)
                    ->where('period', '>=', $currentPeriod)
                    ->whereIn('status', ['unpaid', 'overdue'])
                    ->update(['amount' => $newAmount]);
                
                // Also create missing dues sequentially up to end of year (just in case they were deleted before)
                $year = now()->year;
                for ($m = 1; $m <= 12; $m++) {
                    $period = \Carbon\Carbon::create($year, $m, 1);
                    if ($period->format('Y-m') >= \Carbon\Carbon::now()->format('Y-m')) {
                        \App\Models\Due::firstOrCreate(
                            ['house_id' => $house->id, 'period' => $period->format('Y-m-d')],
                            [
                                'amount' => $newAmount,
                                'status' => 'unpaid',
                                'due_date' => $period->copy()->addDays(9)->format('Y-m-d'),
                            ]
                        );
                    }
                }
            }
        });

        return back()->with('success', 'Data warga berhasil diperbarui.');
    }

    public function recalculateDues(House $house)
    {
        $currentPeriod = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');

        if ($house->is_subsidized) {
            \App\Models\Due::where('house_id', $house->id)
                ->where('period', '>=', $currentPeriod)
                ->whereIn('status', ['unpaid', 'overdue'])
                ->delete();
            return back()->with('success', "Karena rumah {$house->blok}/{$house->nomor} berstatus Bebas Iuran, tagihan yang belum dibayar berhasil dihapus.");
        }

        $newAmount = \App\Services\DuesService::calculate($house);
        $updated = \App\Models\Due::where('house_id', $house->id)
            ->where('period', '>=', $currentPeriod)
            ->whereIn('status', ['unpaid', 'overdue'])
            ->update(['amount' => $newAmount]);

        return back()->with('success', "Berhasil recalculate {$updated} tagihan rumah {$house->blok}/{$house->nomor} menjadi Rp " . number_format($newAmount, 0, ',', '.'));
    }

    public function destroy(House $house)
    {
        $house->delete();
        return back()->with('success', 'Data rumah berhasil dihapus.');
    }

    public function import(Request $request)
    {
        // 1. Validasi file upload
        $request->validate([
            'file' => 'required|file|max:5120', // max 5MB
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['xlsx', 'xls'];

        if (!in_array($extension, $allowedExtensions)) {
            return back()->withErrors([
                'file' => 'Format file tidak didukung. Hanya file Excel (.xlsx, .xls) yang diperbolehkan. Anda mengupload file .' . $extension,
            ]);
        }

        // 2. Coba baca file Excel
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (\Exception $e) {
            return back()->withErrors([
                'file' => 'File tidak bisa dibaca sebagai Excel. Pastikan file tidak corrupt dan formatnya benar (.xlsx/.xls).',
            ]);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();

        // 3. Validasi minimal ada data (header + 1 baris)
        if ($highestRow < 2) {
            $spreadsheet->disconnectWorksheets();
            return back()->withErrors([
                'file' => 'File Excel kosong. Minimal harus ada 1 baris header dan 1 baris data.',
            ]);
        }

        // 4. Validasi header — harus punya minimal 4 kolom
        $colCount = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
        if ($colCount < 4) {
            $spreadsheet->disconnectWorksheets();
            return back()->withErrors([
                'file' => "File hanya memiliki $colCount kolom. Minimal dibutuhkan 4 kolom: Rumah, Nama, Kontak, Status Huni. Download template untuk format yang benar.",
            ]);
        }

        // 5. Validasi nama header
        $headerA = strtolower(trim($sheet->getCell('A1')->getValue() ?? ''));
        $headerD = strtolower(trim($sheet->getCell('D1')->getValue() ?? ''));

        $validHeaderA = in_array($headerA, ['rumah', 'no rumah', 'no_rumah', 'blok/nomor']);
        $validHeaderD = in_array($headerD, ['status huni', 'status_huni', 'statushuni']);

        if (!$validHeaderA || !$validHeaderD) {
            $spreadsheet->disconnectWorksheets();
            $headerActualA = $sheet->getCell('A1')->getValue() ?? '(kosong)';
            $headerActualD = $sheet->getCell('D1')->getValue() ?? '(kosong)';
            return back()->withErrors([
                'file' => "Header kolom tidak sesuai template. Kolom A harus 'Rumah' (ditemukan: '$headerActualA'), Kolom D harus 'Status Huni' (ditemukan: '$headerActualD'). Download template untuk format yang benar.",
            ]);
        }

        // 6. Proses baris per baris dengan validasi detail
        $errors = [];
        $count = 0;
        $validStatusHuni = ['berpenghuni', 'kosong'];
        $validStatusWarga = ['pemilik', 'kontrak', 'belum diketahui', ''];
        $hashedPassword = Hash::make('password'); // Hash sekali, pakai berulang

        DB::beginTransaction();
        try {
            for ($row = 2; $row <= $highestRow; $row++) {
                $rumah = trim($sheet->getCell('A' . $row)->getValue() ?? '');
                $nama = trim($sheet->getCell('B' . $row)->getValue() ?? '');
                $kontak = trim($sheet->getCell('C' . $row)->getValue() ?? '');
                $statusHuni = trim($sheet->getCell('D' . $row)->getValue() ?? '');
                $statusWarga = trim($sheet->getCell('E' . $row)->getValue() ?? '');

                // Skip baris kosong total
                if (empty($rumah) && empty($nama) && empty($statusHuni)) {
                    continue;
                }

                // Validasi kolom Rumah wajib diisi
                if (empty($rumah)) {
                    $errors[] = "Baris $row: Kolom 'Rumah' (A) kosong. Setiap baris harus memiliki nomor rumah (contoh: G1/2).";
                    continue;
                }

                // Validasi format rumah harus mengandung "/"
                if (!str_contains($rumah, '/')) {
                    $errors[] = "Baris $row: Format rumah '$rumah' tidak valid. Harus mengandung tanda '/' (contoh: G1/2, H5/3).";
                    continue;
                }

                // Parse blok & nomor
                $parts = explode('/', $rumah, 2);
                $blok = $parts[0];
                $nomor = $parts[1] ?? '';

                if (empty($blok) || empty($nomor)) {
                    $errors[] = "Baris $row: Format rumah '$rumah' tidak lengkap. Blok dan nomor harus terisi (contoh: G1/2).";
                    continue;
                }

                // Validasi Status Huni
                $statusHuniLower = strtolower($statusHuni);
                if (!empty($statusHuni) && !in_array($statusHuniLower, $validStatusHuni)) {
                    $errors[] = "Baris $row ($rumah): Status Huni '$statusHuni' tidak valid. Gunakan 'Berpenghuni' atau 'Kosong'.";
                    continue;
                }

                // Validasi Status Kepemilikan
                $statusWargaLower = strtolower($statusWarga);
                if (!empty($statusWarga) && !in_array($statusWargaLower, $validStatusWarga)) {
                    $errors[] = "Baris $row ($rumah): Status Kepemilikan '$statusWarga' tidak valid. Gunakan 'Pemilik' atau 'Kontrak'.";
                    continue;
                }

                // Default values
                $statusHuniDb = $statusHuniLower === 'kosong' ? 'kosong' : 'berpenghuni';
                $residentStatus = 'belum_diketahui';
                if ($statusWargaLower === 'pemilik') {
                    $residentStatus = 'pemilik';
                } elseif ($statusWargaLower === 'kontrak') {
                    $residentStatus = 'kontrak';
                }

                // Generate email dari blok+nomor
                $email = strtolower($blok) . '-' . strtolower($nomor) . '@rt44.com';

                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => !empty($nama) ? $nama : 'Warga ' . $rumah,
                        'password' => $hashedPassword,
                        'role' => 'warga',
                        'phone_number' => !empty($kontak) ? $kontak : null,
                        'no_rumah' => "$blok/$nomor",
                    ]
                );

                $house = House::updateOrCreate(
                    ['blok' => $blok, 'nomor' => $nomor],
                    [
                        'status_huni' => $statusHuniDb,
                        'resident_status' => $residentStatus,
                        'owner_id' => $user->id,
                    ]
                );

                // Generate tagihan 12 bulan (Jan-Des) tahun ini
                $year = now()->year;
                $amount = ($house->status_huni === 'berpenghuni') ? 160000 : 110000;

                for ($m = 1; $m <= 12; $m++) {
                    $period = \Carbon\Carbon::createFromDate($year, $m, 1);
                    \App\Models\Due::firstOrCreate(
                        ['house_id' => $house->id, 'period' => $period->format('Y-m-d')],
                        [
                            'amount' => $amount,
                            'status' => 'unpaid',
                            'due_date' => $period->copy()->addDays(9)->format('Y-m-d'),
                        ]
                    );
                }
                $count++;
            }

            // Jika ada error per-baris, rollback & kirim error detail
            if (!empty($errors)) {
                DB::rollBack();
                $spreadsheet->disconnectWorksheets();

                // Batasi maks 10 error ditampilkan agar tidak kebanjiran
                $totalErrors = count($errors);
                $displayErrors = array_slice($errors, 0, 10);
                $errorMessage = implode("\n", $displayErrors);
                if ($totalErrors > 10) {
                    $errorMessage .= "\n...dan " . ($totalErrors - 10) . " error lainnya.";
                }

                return back()->withErrors([
                    'file' => "Ditemukan $totalErrors kesalahan dalam file. Perbaiki lalu upload ulang:\n" . $errorMessage,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $spreadsheet->disconnectWorksheets();
            return back()->withErrors([
                'file' => 'Gagal menyimpan data ke database. Kemungkinan ada data yang duplikat atau format tidak sesuai. Pastikan file Excel mengikuti template yang disediakan.',
            ]);
        }

        $spreadsheet->disconnectWorksheets();
        return back()->with('success', "$count data warga berhasil diimport dari Excel.");
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Data Warga');

        // Headers
        $headers = ['Rumah', 'Pemilik / Penghuni', 'Kontak', 'Status Huni', 'Status Kepemilikan'];
        foreach ($headers as $i => $header) {
            $col = chr(65 + $i); // A, B, C, D, E
            $sheet->setCellValue($col . '1', $header);
        }

        // Style header
        $headerRange = 'A1:E1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Example rows
        $examples = [
            ['G1/2', 'HANS KARTONO', '08123456789', 'Berpenghuni', 'Pemilik'],
            ['G1/3', '', '', 'Kosong', ''],
            ['G1/6', 'ABIYU', '', 'Berpenghuni', 'Kontrak'],
        ];
        foreach ($examples as $i => $row) {
            foreach ($row as $j => $value) {
                $col = chr(65 + $j);
                $sheet->setCellValue($col . ($i + 2), $value);
            }
        }

        // Style example rows
        $sheet->getStyle('A2:E4')->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Info/petunjuk di baris ke-6
        $sheet->setCellValue('A6', 'PETUNJUK:');
        $sheet->getStyle('A6')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
        $sheet->setCellValue('A7', '- Kolom Rumah: format Blok/Nomor (contoh: G1/2, H5/3)');
        $sheet->setCellValue('A8', '- Kolom Status Huni: isi "Berpenghuni" atau "Kosong"');
        $sheet->setCellValue('A9', '- Kolom Status Kepemilikan: isi "Pemilik" atau "Kontrak" (kosongkan jika tidak diketahui)');
        $sheet->setCellValue('A10', '- Kolom Nama & Kontak boleh dikosongkan');
        $sheet->setCellValue('A11', '- Hapus baris contoh (baris 2-4) dan petunjuk ini sebelum upload');

        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Generate file
        $fileName = 'template_data_warga_rt44.xlsx';
        $tempPath = storage_path('app/' . $fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);
        $spreadsheet->disconnectWorksheets();

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
