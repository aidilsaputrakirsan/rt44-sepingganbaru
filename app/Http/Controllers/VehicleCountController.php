<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\VehicleCount;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleCountController extends Controller
{
    /**
     * Urutan natural blok (G1, G2, ..., G10) dipakai di semua query rumah pada project ini.
     */
    private const NATURAL_SORT = "REGEXP_SUBSTR(blok, '^[A-Za-z]+'), CAST(REGEXP_SUBSTR(blok, '[0-9]+') AS UNSIGNED), CAST(nomor AS UNSIGNED)";

    private function guardKetua(): void
    {
        if (auth()->user()?->role !== 'ketua') {
            abort(403, 'Halaman ini hanya untuk Ketua RT.');
        }
    }

    /**
     * Form publik (tanpa login) untuk warga mengisi data kendaraan.
     */
    public function create()
    {
        $houses = House::with(['owner:id,name', 'tenant:id,name', 'vehicleCount'])
            ->orderByRaw(self::NATURAL_SORT)
            ->get()
            ->map(fn (House $house) => [
                'id' => $house->id,
                'label' => trim($house->blok . '/' . $house->nomor . ' - ' . ($house->owner?->name ?? $house->tenant?->name ?? '-')),
                'jumlah_mobil' => $house->vehicleCount?->jumlah_mobil,
                'jumlah_motor' => $house->vehicleCount?->jumlah_motor,
                'diisi_pada' => $house->vehicleCount?->updated_at?->isoFormat('D MMM Y, HH:mm'),
            ]);

        return Inertia::render('Public/PendataanKendaraan', [
            'houses' => $houses,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'house_id' => 'required|exists:houses,id',
            'jumlah_mobil' => 'required|integer|min:0|max:20',
            'jumlah_motor' => 'required|integer|min:0|max:20',
        ]);

        VehicleCount::updateOrCreate(
            ['house_id' => $validated['house_id']],
            [
                'jumlah_mobil' => $validated['jumlah_mobil'],
                'jumlah_motor' => $validated['jumlah_motor'],
            ]
        );

        return back()->with('success', 'Data kendaraan berhasil disimpan. Terima kasih!');
    }

    /**
     * Rekap data kendaraan untuk Ketua RT.
     */
    public function index()
    {
        $this->guardKetua();

        $houses = $this->rekapHouses();

        return Inertia::render('Ketua/Kendaraan', [
            'houses' => $houses,
            'ringkasan' => $this->ringkasan($houses),
        ]);
    }

    public function exportPdf()
    {
        $this->guardKetua();

        $houses = $this->rekapHouses();

        $pdf = Pdf::loadView('reports.kendaraan', [
            'houses' => $houses,
            'ringkasan' => $this->ringkasan($houses),
            'generatedAt' => now()->translatedFormat('d F Y'),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('Data_Kendaraan_RT44_' . now()->format('Y-m-d') . '.pdf');
    }

    private function rekapHouses()
    {
        return House::with(['owner:id,name', 'tenant:id,name', 'vehicleCount'])
            ->orderByRaw(self::NATURAL_SORT)
            ->get()
            ->map(fn (House $house) => [
                'rumah' => $house->blok . '/' . $house->nomor,
                'nama' => $house->owner?->name ?? $house->tenant?->name ?? '-',
                'jumlah_mobil' => $house->vehicleCount->jumlah_mobil ?? 0,
                'jumlah_motor' => $house->vehicleCount->jumlah_motor ?? 0,
                'sudah_isi' => (bool) $house->vehicleCount,
                'diisi_pada' => $house->vehicleCount?->updated_at?->isoFormat('D MMM Y, HH:mm'),
            ]);
    }

    private function ringkasan($houses): array
    {
        return [
            'total_rumah' => $houses->count(),
            'sudah_isi' => $houses->where('sudah_isi', true)->count(),
            'total_mobil' => $houses->sum('jumlah_mobil'),
            'total_motor' => $houses->sum('jumlah_motor'),
        ];
    }
}
