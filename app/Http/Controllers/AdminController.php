<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\Due;
use App\Models\House;
use App\Models\Payment;

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

        $houses = House::with('owner')->orderBy('blok')->orderBy('nomor')->get();
        // Get dues with payments to calculate paid amount
        $dues = Due::with('payments')
            ->whereYear('period', $year)
            ->get();

        $calendar = $houses->map(function ($house) use ($dues, $year) {
            $data = [
                'id' => $house->id,
                'name' => $house->blok . '/' . $house->nomor,
                'owner' => $house->owner ? $house->owner->name : '-',
                'months' => []
            ];

            for ($m = 1; $m <= 12; $m++) {
                // Carbon period is Y-m-d. Match month and house.
                $monthDue = $dues->filter(function ($d) use ($house, $m) {
                    return $d->house_id === $house->id && Carbon::parse($d->period)->month === $m;
                })->first();

                // Calculate total verified payments for this due
                $paidAmount = 0;
                $manualPaymentId = null;
                if ($monthDue) {
                    $paidAmount = $monthDue->payments->where('status', 'verified')->sum('amount_paid');
                    // Find the first manual payment to allow 'editing'
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
}
