<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\Due;
use App\Models\House;
use App\Models\Payment;
use App\Services\FonnteService;

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
            ->orderBy('house_id')
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
            ->orderBy('blok')
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
                'months' => []
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
            }
            return $data;
        });

        return Inertia::render('Admin/Calendar', [
            'calendar' => $calendar,
            'year' => (int) $year,
        ]);
    }

    public function storePayment(Request $request, Due $due)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $house = $due->house;
        $statusHuni = $house->status_huni; // 'berpenghuni' or 'kosong'

        // Define Wajib amount based on status
        $wajibAmount = ($statusHuni === 'berpenghuni') ? 160000 : 110000;

        $amountPaid = $request->amount;
        $amountWajib = min($amountPaid, $wajibAmount);
        $amountSukarela = max(0, $amountPaid - $wajibAmount);

        // Use updateOrCreate to avoid accumulation for manual entries by Admin
        // We identify the 'manual' payment for this due
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
            ]
        );

        // Check if fully paid (mandatory portion)
        $totalPaidWajib = $due->payments()->where('status', 'verified')->sum('amount_wajib');

        if ($totalPaidWajib >= $due->amount) {
            $due->update(['status' => 'paid']);
        } else {
            $due->update(['status' => 'unpaid']); // In case it was paid and then updated to lower
        }

        return back()->with('success', 'Data pembayaran berhasil diperbarui.');
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

        // Ambil hanya tagihan tertua yang belum lunas
        $oldestDue = Due::where('house_id', $house->id)
            ->where('status', 'unpaid')
            ->whereYear('period', $year)
            ->orderBy('period', 'asc')
            ->first();

        if (!$oldestDue) {
            return back()->with('error', 'Semua iuran untuk tahun ' . $year . ' sudah lunas.');
        }

        $monthName = Carbon::parse($oldestDue->period)->translatedFormat('F');
        $amountStr = "Rp " . number_format($oldestDue->amount, 0, ',', '.');

        // Format pesan profesional ala Ketua RT
        $message = "Assalamu'alaikum Bapak/Ibu {$owner->name},\n\n"
            . "Kami menginformasikan tagihan iuran RT 44 Sepinggan Baru tahun {$year} untuk rumah {$house->blok}/{$house->nomor}.\n\n"
            . "📌 *Bulan {$monthName}*: {$amountStr}\n\n"
            . "Sebagai informasi, nominal tersebut merupakan iuran wajib bulanan. Bagi Bapak/Ibu yang ingin berpartisipasi lebih melalui dana sukarela, kami sangat mengapresiasi dukungan tersebut untuk pengembangan lingkungan kita bersama. ✅\n\n"
            . "Terima kasih atas partisipasi dan kerja samanya. 🙏\n\n"
            . "Salam,\n"
            . "*Ketua RT 44*";

        $fonnte = new FonnteService();
        $result = $fonnte->send($owner->phone_number, $message);

        if ($result['success']) {
            return back()->with('success', 'Reminder WA berhasil dikirim ke ' . $owner->name);
        }

        return back()->with('error', $result['message']);
    }
}
