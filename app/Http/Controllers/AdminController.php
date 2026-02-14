<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\Due;
use App\Models\House;
use App\Models\Payment;
use App\Services\FonnteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('dashboard');
        }

        // Empty dashboard for now as requested
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'pending' => 0,
                'collected' => 0,
                'unpaid' => 0,
            ],
            'dues' => [],
        ]);
    }

    public function tagihan()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('dashboard');
        }

        $currentPeriod = Carbon::today()->startOfMonth();

        // Get list of dues for "Tagihan Data Warga" page
        $dues = Due::with(['house.owner'])
            ->where('period', $currentPeriod)
            ->join('houses', 'dues.house_id', '=', 'houses.id')
            ->orderByRaw("REGEXP_SUBSTR(houses.blok, '^[A-Za-z]+') ASC")
            ->orderByRaw("CAST(REGEXP_SUBSTR(houses.blok, '[0-9]+') AS UNSIGNED) ASC")
            ->orderByRaw('CAST(houses.nomor AS UNSIGNED) ASC')
            ->orderBy('houses.nomor')
            ->select('dues.*')
            ->get()
            ->map(function ($due) {
                return [
                    'id' => $due->id,
                    'house' => $due->house->blok . '/' . $due->house->nomor,
                    'owner' => $due->house->owner ? $due->house->owner->name : 'No Owner',
                    'amount' => $due->amount,
                    'status' => $due->status,
                ];
            });

        return Inertia::render('Admin/TagihanDataWarga', [
            'dues' => $dues,
        ]);
    }

    public function bulkUpdateDues(Request $request)
    {
        $request->validate([
            'amount_berpenghuni' => 'nullable|numeric|min:0',
            'amount_kosong' => 'nullable|numeric|min:0',
        ]);

        if ($request->amount_berpenghuni === null && $request->amount_kosong === null) {
            return back()->with('error', 'Minimal isi salah satu nominal.');
        }

        $currentPeriod = Carbon::today()->startOfMonth();
        $updated = 0;

        if ($request->amount_berpenghuni !== null) {
            $count = Due::where('period', $currentPeriod)
                ->whereHas('house', fn($q) => $q->where('status_huni', 'berpenghuni'))
                ->update(['amount' => $request->amount_berpenghuni]);
            $updated += $count;
        }

        if ($request->amount_kosong !== null) {
            $count = Due::where('period', $currentPeriod)
                ->whereHas('house', fn($q) => $q->where('status_huni', 'kosong'))
                ->update(['amount' => $request->amount_kosong]);
            $updated += $count;
        }

        return back()->with('success', "Berhasil memperbarui {$updated} tagihan.");
    }

    public function updateDue(Request $request, Due $due)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $due->update(['amount' => $request->amount]);

        return back()->with('success', 'Nominal tagihan berhasil diperbarui.');
    }

    public function calendar(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('dashboard');
        }

        $year = $request->input('year', now()->year);

        $houses = House::with('owner')
            ->orderByRaw("REGEXP_SUBSTR(blok, '^[A-Za-z]+') ASC")
            ->orderByRaw("CAST(REGEXP_SUBSTR(blok, '[0-9]+') AS UNSIGNED) ASC")
            ->orderByRaw('CAST(nomor AS UNSIGNED) ASC')
            ->orderBy('nomor')
            ->get();
        // Get dues with payments to calculate paid amount
        $dues = Due::with('payments')
            ->whereYear('period', $year)
            ->get();

        // Pre-group dues by "house_id-month" for O(1) lookup instead of O(N) filter per cell
        $duesGrouped = $dues->keyBy(function ($d) {
            return $d->house_id . '-' . Carbon::parse($d->period)->month;
        });

        $calendar = $houses->map(function ($house) use ($duesGrouped) {
            $data = [
                'id' => $house->id,
                'name' => $house->blok . '/' . $house->nomor,
                'owner' => $house->owner ? $house->owner->name : '-',
                'phone' => $house->owner ? $house->owner->phone_number : null,
                'months' => [],
                'total_unpaid' => 0,
                'unpaid_months_count' => 0,
            ];

            for ($m = 1; $m <= 12; $m++) {
                $monthDue = $duesGrouped->get($house->id . '-' . $m);

                // Calculate total verified payments for this due
                $paidAmount = 0;
                $manualPaymentId = null;
                if ($monthDue) {
                    $paidAmount = $monthDue->payments->where('status', 'verified')->sum('amount_paid');
                    $manualPayment = $monthDue->payments->where('method', 'manual')->first();
                    $manualPaymentId = $manualPayment ? $manualPayment->id : null;
                }

                $data['months'][$m] = [
                    'due_id' => $monthDue ? $monthDue->id : null,
                    'manual_payment_id' => $manualPaymentId,
                    'status' => $monthDue ? $monthDue->status : 'none',
                    'bill_amount' => $monthDue ? $monthDue->amount : 0,
                    'paid_amount' => $paidAmount,
                    'is_paid' => $monthDue ? ($monthDue->status === 'paid') : false,
                ];

                // Aggregate unpaid
                if ($monthDue && $monthDue->status !== 'paid') {
                    $unpaid = max(0, $monthDue->amount - $paidAmount);
                    if ($unpaid > 0) {
                        $data['total_unpaid'] += $unpaid;
                        $data['unpaid_months_count']++;
                    }
                }
            }
            return $data;
        });

        return Inertia::render('Admin/Calendar', [
            'calendar' => $calendar,
            'year' => (int) $year,
        ]);
    }

    public function exportCalendarPdf(Request $request)
    {
        $year = $request->input('year', now()->year);

        $houses = House::with('owner')
            ->orderByRaw("REGEXP_SUBSTR(blok, '^[A-Za-z]+') ASC")
            ->orderByRaw("CAST(REGEXP_SUBSTR(blok, '[0-9]+') AS UNSIGNED) ASC")
            ->orderByRaw('CAST(nomor AS UNSIGNED) ASC')
            ->orderBy('nomor')
            ->get();

        $dues = Due::with('payments')
            ->whereYear('period', $year)
            ->get();

        $duesGrouped = $dues->keyBy(function ($d) {
            return $d->house_id . '-' . Carbon::parse($d->period)->month;
        });

        $calendar = $houses->map(function ($house) use ($duesGrouped) {
            $data = [
                'name' => $house->blok . '/' . $house->nomor,
                'months' => []
            ];

            for ($m = 1; $m <= 12; $m++) {
                $monthDue = $duesGrouped->get($house->id . '-' . $m);
                $paidAmount = 0;
                if ($monthDue) {
                    $paidAmount = $monthDue->payments->where('status', 'verified')->sum('amount_paid');
                }

                $data['months'][$m] = [
                    'status' => $monthDue ? $monthDue->status : 'none',
                    'paid_amount' => $paidAmount,
                ];
            }
            return $data;
        })->toArray();

        $pdf = Pdf::loadView('reports.calendar', [
            'calendar' => $calendar,
            'year' => (int) $year,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream("Kalender_Iuran_RT44_{$year}.pdf");
    }

    public function storePayment(Request $request, Due $due)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0|max:99999999',
            'payment_date' => 'nullable|date|before_or_equal:today',
        ], [
            'amount.max' => 'Nominal tidak boleh melebihi Rp 99.999.999.',
        ]);

        $house = $due->house;
        $amountPaid = $request->amount;
        $paymentDate = $request->payment_date ?? now()->toDateString();

        // Jika 0, hapus payment manual dan kembalikan status due
        if ($amountPaid == 0) {
            Payment::where('due_id', $due->id)->where('method', 'manual')->delete();

            $totalPaidWajib = $due->payments()->where('status', 'verified')->sum('amount_wajib');
            $due->update(['status' => $totalPaidWajib >= $due->amount ? 'paid' : 'unpaid']);

            return back()->with('success', 'Pembayaran manual berhasil dihapus.');
        }

        $wajibAmount = $due->amount;

        $amountWajib = min($amountPaid, $wajibAmount);
        $amountSukarela = max(0, $amountPaid - $wajibAmount);

        Payment::updateOrCreate(
            [
                'due_id' => $due->id,
                'method' => 'manual',
            ],
            [
                'recorded_by' => auth()->id(),
                'payer_id' => $house->owner_id,
                'amount_paid' => $amountPaid,
                'amount_wajib' => $amountWajib,
                'amount_sukarela' => $amountSukarela,
                'status' => 'verified',
                'verified_at' => now(),
                'payment_date' => $paymentDate,
            ]
        );

        $totalPaidWajib = $due->payments()->where('status', 'verified')->sum('amount_wajib');

        if ($totalPaidWajib >= $due->amount) {
            $due->update(['status' => 'paid']);
        } else {
            $due->update(['status' => 'unpaid']);
        }

        return back()->with('success', 'Data pembayaran berhasil diperbarui.');
    }

    public function storeLumpSumPayment(Request $request, House $house)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0|max:99999999',
            'payment_date' => 'nullable|date|before_or_equal:today',
            'year' => 'required|integer',
            'due_ids' => 'required|array|min:1',
            'due_ids.*' => 'integer|exists:dues,id',
        ], [
            'amount.max' => 'Nominal tidak boleh melebihi Rp 99.999.999.',
        ]);

        $amountPaid = $request->amount;
        $paymentDate = $request->payment_date ?? now()->toDateString();
        $dueIds = $request->due_ids;

        // Ambil dues yang dipilih, pastikan milik rumah ini, urut dari terlama
        $selectedDues = Due::where('house_id', $house->id)
            ->whereIn('id', $dueIds)
            ->orderBy('period', 'asc')
            ->get();

        if ($selectedDues->isEmpty()) {
            return back()->with('error', 'Tidak ada tagihan yang dipilih.');
        }

        // Jika 0, hapus manual payment untuk dues yang dipilih
        if ($amountPaid == 0) {
            Payment::whereIn('due_id', $selectedDues->pluck('id'))->where('method', 'manual')->delete();

            foreach ($selectedDues as $due) {
                $totalPaidWajib = $due->payments()->where('status', 'verified')->sum('amount_wajib');
                $due->update(['status' => $totalPaidWajib >= $due->amount ? 'paid' : 'unpaid']);
            }

            return back()->with('success', 'Pembayaran manual untuk bulan yang dipilih berhasil dihapus.');
        }

        DB::beginTransaction();
        try {
            // Hapus manual payment lama untuk dues yang dipilih (reset, lalu alokasi ulang)
            Payment::whereIn('due_id', $selectedDues->pluck('id'))->where('method', 'manual')->delete();

            $remaining = $amountPaid;
            $lastAllocatedDue = null;

            foreach ($selectedDues as $due) {
                $existingPaidWajib = $due->payments()
                    ->where('status', 'verified')
                    ->where('method', '!=', 'manual')
                    ->sum('amount_wajib');
                $dueRemaining = max(0, $due->amount - $existingPaidWajib);

                if ($dueRemaining <= 0) {
                    $due->update(['status' => 'paid']);
                    continue;
                }

                if ($remaining <= 0) {
                    $due->update(['status' => 'unpaid']);
                    continue;
                }

                $allocate = min($remaining, $dueRemaining);
                $remaining -= $allocate;

                Payment::create([
                    'due_id' => $due->id,
                    'recorded_by' => auth()->id(),
                    'payer_id' => $house->owner_id,
                    'amount_paid' => $allocate,
                    'amount_wajib' => $allocate,
                    'amount_sukarela' => 0,
                    'method' => 'manual',
                    'status' => 'verified',
                    'verified_at' => now(),
                    'payment_date' => $paymentDate,
                ]);

                $lastAllocatedDue = $due;

                $totalPaidWajib = $existingPaidWajib + $allocate;
                $due->update(['status' => $totalPaidWajib >= $due->amount ? 'paid' : 'unpaid']);
            }

            // Sisa setelah semua due yang dipilih lunas → sukarela di due terakhir
            if ($remaining > 0 && $lastAllocatedDue) {
                $lastPayment = Payment::where('due_id', $lastAllocatedDue->id)
                    ->where('method', 'manual')
                    ->first();

                if ($lastPayment) {
                    $lastPayment->update([
                        'amount_paid' => $lastPayment->amount_paid + $remaining,
                        'amount_sukarela' => $remaining,
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan pembayaran: ' . $e->getMessage());
        }

        $count = $selectedDues->count();
        return back()->with('success', "Pembayaran {$count} bulan berhasil dicatat untuk rumah {$house->blok}/{$house->nomor}.");
    }

    /**
     * Build reminder message for a house with all unpaid dues up to cutoff date.
     * Cutoff: tanggal 5 setiap bulan — bulan berjalan dianggap jatuh tempo setelah tanggal 5.
     */
    private function buildReminderMessage(House $house, int $year): ?array
    {
        $owner = $house->owner;
        if (!$owner) return null;

        // Tentukan bulan cutoff: jika tanggal >= 5, bulan ini termasuk; jika < 5, sampai bulan lalu
        $today = Carbon::today();
        if ($today->year == $year) {
            $cutoffMonth = $today->day >= 5 ? $today->month : $today->month - 1;
        } else if ($today->year > $year) {
            $cutoffMonth = 12; // Tahun lalu, semua bulan
        } else {
            $cutoffMonth = 0; // Tahun depan, belum ada yang jatuh tempo
        }

        if ($cutoffMonth < 1) return null;

        $cutoffDate = Carbon::create($year, $cutoffMonth, 1)->endOfMonth();

        // Ambil semua tagihan belum lunas sampai bulan cutoff (dengan payments untuk hitung sisa)
        $unpaidDues = Due::with('payments')
            ->where('house_id', $house->id)
            ->where('status', 'unpaid')
            ->whereYear('period', $year)
            ->where('period', '<=', $cutoffDate)
            ->orderBy('period', 'asc')
            ->get();

        if ($unpaidDues->isEmpty()) return null;

        // Build detail per bulan — hitung sisa (tagihan - sudah dibayar verified)
        $details = [];
        $totalAmount = 0;
        foreach ($unpaidDues as $due) {
            $paidAmount = $due->payments->where('status', 'verified')->sum('amount_wajib');
            $remaining = max(0, $due->amount - $paidAmount);
            if ($remaining <= 0) continue;

            $monthName = Carbon::parse($due->period)->translatedFormat('F');
            $remainingStr = "Rp " . number_format($remaining, 0, ',', '.');

            if ($paidAmount > 0) {
                $details[] = "• *{$monthName}*: {$remainingStr} (sisa)";
            } else {
                $details[] = "• *{$monthName}*: {$remainingStr}";
            }
            $totalAmount += $remaining;
        }

        if (empty($details)) return null;

        $totalStr = "Rp " . number_format($totalAmount, 0, ',', '.');
        $detailText = implode("\n", $details);
        $bulanCount = count($unpaidDues);

        $message = "Assalamu'alaikum Bapak/Ibu {$owner->name},\n\n"
            . "Kami menginformasikan tagihan iuran RT 44 Sepinggan Baru tahun {$year} untuk rumah {$house->blok}/{$house->nomor} yang belum lunas.\n\n"
            . "📌 *Rincian Tagihan ({$bulanCount} bulan):*\n"
            . "{$detailText}\n\n"
            . "💰 *Total: {$totalStr}*\n\n"
            . "Mohon untuk segera melakukan pembayaran. Bagi Bapak/Ibu yang ingin berpartisipasi lebih melalui dana sukarela, kami sangat mengapresiasi dukungan tersebut untuk pengembangan lingkungan kita bersama. ✅\n\n"
            . "Terima kasih atas partisipasi dan kerja samanya. 🙏\n\n"
            . "Salam,\n"
            . "*Ketua RT 44*";

        return [
            'message' => $message,
            'unpaid_count' => $bulanCount,
            'total_amount' => $totalAmount,
        ];
    }

    public function previewReminder(Request $request, House $house)
    {
        $request->validate(['year' => 'required|integer']);

        $result = $this->buildReminderMessage($house, $request->year);

        if (!$result) {
            return response()->json([
                'message' => null,
                'error' => 'Semua iuran sudah lunas atau belum jatuh tempo.',
            ]);
        }

        return response()->json([
            'message' => $result['message'],
            'unpaid_count' => $result['unpaid_count'],
            'total_amount' => $result['total_amount'],
        ]);
    }

    public function sendReminder(Request $request, House $house)
    {
        $request->validate([
            'year' => 'required|integer',
        ]);

        $year = $request->year;
        $owner = $house->owner;

        if (!$owner || !$owner->phone_number) {
            return back()->with('error', 'Nomor HP pemilik rumah ' . $house->blok . '/' . $house->nomor . ' belum terdaftar.');
        }

        $result = $this->buildReminderMessage($house, $year);

        if (!$result) {
            return back()->with('error', 'Semua iuran untuk tahun ' . $year . ' sudah lunas atau belum jatuh tempo.');
        }

        $fonnte = new FonnteService();
        $sendResult = $fonnte->send($owner->phone_number, $result['message']);

        if ($sendResult['success']) {
            return back()->with('success', 'Reminder WA berhasil dikirim ke ' . $owner->name);
        }

        return back()->with('error', $sendResult['message']);
    }
}
