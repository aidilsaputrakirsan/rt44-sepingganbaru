<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class WargaController extends Controller
{
    public function index()
    {
        $houses = House::with('owner')
            ->orderBy('blok')
            ->orderBy('nomor')
            ->get();

        return Inertia::render('Admin/Warga/Index', [
            'houses' => $houses
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'blok' => 'required|string',
            'nomor' => 'required|string',
            'status_huni' => 'required|in:berpenghuni,kosong',
            'resident_status' => 'required|in:pemilik,kontrak',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone_number' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'] ?? 'Warga ' . $validated['blok'] . '/' . $validated['nomor'],
                'email' => $validated['email'] ?? strtolower($validated['blok'] . $validated['nomor']) . '@rt44.com',
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
                'owner_id' => $user->id,
            ]);

            // Create initial due for the current month
            $currentPeriod = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
            $amount = ($house->status_huni === 'berpenghuni') ? 160000 : 110000;

            \App\Models\Due::create([
                'house_id' => $house->id,
                'period' => $currentPeriod,
                'amount' => $amount,
                'status' => 'unpaid'
            ]);
        });

        return back()->with('success', 'Data warga berhasil ditambahkan.');
    }

    public function update(Request $request, House $house)
    {
        $validated = $request->validate([
            'status_huni' => 'required|in:berpenghuni,kosong',
            'resident_status' => 'required|in:pemilik,kontrak',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . ($house->owner_id ?? 0),
            'phone_number' => 'nullable|string',
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
            $house->save();

            // Auto-update current month's bill amount based on status_huni
            $currentPeriod = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
            $due = \App\Models\Due::where('house_id', $house->id)
                ->where('period', $currentPeriod)
                ->first();

            if ($due) {
                // Determine Bill Amount: 160k for occupied, 110k for empty
                $newAmount = ($house->status_huni === 'berpenghuni') ? 160000 : 110000;
                $due->update(['amount' => $newAmount]);
            }
        });

        return back()->with('success', 'Data warga berhasil diperbarui.');
    }

    public function destroy(House $house)
    {
        $house->delete();
        return back()->with('success', 'Data rumah berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt,xlsx'
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), "r");

        // Skip header
        $header = fgetcsv($handle, 1000, ",");

        $count = 0;
        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Example CSV: Blok, Nomor, Nama, Email, Phone, Status Huni (berpenghuni/kosong), Status Residen (pemilik/kontrak)
                $blok = $data[0] ?? '';
                $nomor = $data[1] ?? '';
                $nama = $data[2] ?? '';
                $email = $data[3] ?? '';
                $phone = $data[4] ?? '';
                $statusHuni = strtolower($data[5] ?? 'berpenghuni');
                $statusResiden = strtolower($data[6] ?? 'pemilik');

                if (empty($blok) || empty($nomor))
                    continue;

                $user = User::updateOrCreate(
                    ['email' => $email ?: strtolower($blok . $nomor) . '@rt44.com'],
                    [
                        'name' => $nama,
                        'password' => $email ? Hash::make('password') : Hash::make('password'), // default
                        'role' => 'warga',
                        'phone_number' => $phone,
                        'no_rumah' => "$blok/$nomor",
                    ]
                );

                $house = House::updateOrCreate(
                    ['blok' => $blok, 'nomor' => $nomor],
                    [
                        'status_huni' => $statusHuni,
                        'resident_status' => $statusResiden,
                        'owner_id' => $user->id,
                    ]
                );

                // Ensure current month due exists
                $currentPeriod = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
                $amount = ($house->status_huni === 'berpenghuni') ? 160000 : 110000;

                \App\Models\Due::firstOrCreate(
                    ['house_id' => $house->id, 'period' => $currentPeriod],
                    ['amount' => $amount, 'status' => 'unpaid']
                );
                $count++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal import data: ' . $e->getMessage());
        }

        return back()->with('success', "$count data warga berhasil diimport.");
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_warga_rt44.csv"',
        ];

        $columns = ['Blok', 'Nomor', 'Nama', 'Email', 'Phone', 'StatusHuni', 'StatusResiden'];
        $example = ['G', '1', 'Budi Santoso', 'budi@example.com', '08123456789', 'berpenghuni', 'pemilik'];

        $callback = function () use ($columns, $example) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $example);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
