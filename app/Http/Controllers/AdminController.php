<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('dashboard');
        }

        $currentPeriod = \Carbon\Carbon::today()->startOfMonth();

        $pendingPayments = \App\Models\Payment::where('status', 'pending')->count();
        $totalCollected = \App\Models\Payment::where('status', 'verified')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount_paid');
        
        $unpaidCount = \App\Models\Due::where('period', $currentPeriod)
            ->where('status', 'unpaid')
            ->count();

        // Get list of dues for Checklist
        $dues = \App\Models\Due::with(['house.owner', 'payments'])
            ->where('period', $currentPeriod)
            ->orderBy('house_id') // Sort by Block/Number is tricky with ID, maybe join houses?
            ->get()
            ->map(function ($due) {
                return [
                    'id' => $due->id,
                    'house' => $due->house->blok . '/' . $due->house->nomor,
                    'owner' => $due->house->owner ? $due->house->owner->name : 'No Owner',
                    'amount' => $due->amount,
                    'status' => $due->status,
                    'is_paid' => $due->status === 'paid',
                ];
            });
        
        return \Inertia\Inertia::render('Admin/Dashboard', [
            'stats' => [
                'pending' => $pendingPayments,
                'collected' => $totalCollected,
                'unpaid' => $unpaidCount,
            ],
            'dues' => $dues,
        ]);
    }

    public function calendar(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('dashboard');
        }

        $year = $request->input('year', now()->year);
        
        $houses = \App\Models\House::orderBy('blok')->orderBy('nomor')->get();
        $dues = \App\Models\Due::whereYear('period', $year)->get();

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
                    return $d->house_id === $house->id && \Carbon\Carbon::parse($d->period)->month === $m;
                })->first();

                $data['months'][$m] = [
                    'status' => $monthDue ? $monthDue->status : 'none',
                    'amount' => $monthDue ? $monthDue->amount : 0,
                    'is_paid' => $monthDue ? ($monthDue->status === 'paid') : false,
                ];
            }
            return $data;
        });

        return \Inertia\Inertia::render('Admin/Calendar', [
            'calendar' => $calendar,
            'year' => (int)$year,
        ]);
    }
}
