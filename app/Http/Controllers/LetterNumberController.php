<?php

namespace App\Http\Controllers;

use App\Models\LetterNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class LetterNumberController extends Controller
{
    private function guard(): void
    {
        if (!in_array(auth()->user()->role, ['ketua', 'admin', 'demo'])) {
            abort(403);
        }
    }

    public function index()
    {
        $this->guard();

        $entries = LetterNumber::with('creator:id,name')
            ->orderByDesc('tahun')
            ->orderByDesc('nomor_urut')
            ->get()
            ->map(fn ($e) => [
                'id'           => $e->id,
                'nomor_urut'   => $e->nomor_urut,
                'nomor_format' => $e->nomor_format,
                'tahun'        => $e->tahun,
                'bulan'        => $e->bulan,
                'jenis'        => $e->jenis,
                'keterangan'   => $e->keterangan,
                'tanggal'      => $e->tanggal->format('Y-m-d'),
                'created_by'   => $e->creator?->name,
                'has_pdf'      => !empty($e->payload),
            ]);

        $tahun = (int) now()->year;

        return Inertia::render('Ketua/AgendaSurat', [
            'entries'    => $entries,
            'nextNumber' => [
                'nomor_urut'   => LetterNumber::nextNomorUrut($tahun),
                'tahun'        => $tahun,
                'bulan'        => (int) now()->month,
                'nomor_format' => LetterNumber::format(LetterNumber::nextNomorUrut($tahun), (int) now()->month, $tahun),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->guard();

        $validated = $request->validate([
            'tanggal'    => 'required|date',
            'jenis'      => 'required|string|max:100',
            'keterangan' => 'nullable|string|max:200',
            'nomor_urut' => 'nullable|integer|min:1',
        ]);

        $tanggal = Carbon::parse($validated['tanggal']);
        $tahun = (int) $tanggal->year;

        $nomorUrut = $validated['nomor_urut'] ?? LetterNumber::nextNomorUrut($tahun);

        // Cegah duplikat nomor dalam tahun yang sama
        if (LetterNumber::where('tahun', $tahun)->where('nomor_urut', $nomorUrut)->exists()) {
            return back()->withErrors(['nomor_urut' => "Nomor {$nomorUrut} sudah dipakai di tahun {$tahun}."]);
        }

        LetterNumber::create([
            'nomor_urut' => $nomorUrut,
            'tahun'      => $tahun,
            'bulan'      => (int) $tanggal->month,
            'jenis'      => $validated['jenis'],
            'keterangan' => $validated['keterangan'] ?? null,
            'tanggal'    => $tanggal->toDateString(),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Nomor surat ditambahkan ke agenda.');
    }

    public function update(Request $request, LetterNumber $letterNumber)
    {
        $this->guard();

        $validated = $request->validate([
            'tanggal'    => 'required|date',
            'jenis'      => 'required|string|max:100',
            'keterangan' => 'nullable|string|max:200',
            'nomor_urut' => 'required|integer|min:1',
        ]);

        $tanggal = Carbon::parse($validated['tanggal']);
        $tahun = (int) $tanggal->year;

        $dup = LetterNumber::where('tahun', $tahun)
            ->where('nomor_urut', $validated['nomor_urut'])
            ->where('id', '!=', $letterNumber->id)
            ->exists();
        if ($dup) {
            return back()->withErrors(['nomor_urut' => "Nomor {$validated['nomor_urut']} sudah dipakai di tahun {$tahun}."]);
        }

        $letterNumber->update([
            'nomor_urut' => $validated['nomor_urut'],
            'tahun'      => $tahun,
            'bulan'      => (int) $tanggal->month,
            'jenis'      => $validated['jenis'],
            'keterangan' => $validated['keterangan'] ?? null,
            'tanggal'    => $tanggal->toDateString(),
        ]);

        return back()->with('success', 'Nomor surat diperbarui.');
    }

    public function destroy(LetterNumber $letterNumber)
    {
        $this->guard();

        $letterNumber->delete();

        return back()->with('success', 'Nomor surat dihapus dari agenda.');
    }
}
