<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Safety check
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        $houses = $user->houses()->with(['dues' => function($q) {
            $q->orderBy('period', 'desc');
        }])->get();

        return \Inertia\Inertia::render('Dashboard', [
            'houses' => $houses
        ]);
    }

    public function calendar(Request $request)
    {
        $user = auth()->user();
        $year = $request->input('year', now()->year);
        
        $houses = $user->houses()->with(['dues' => function($q) use ($year) {
            $q->whereYear('period', $year);
        }])->get();

        $calendar = $houses->map(function ($house) use ($year) {
            $data = [
                'id' => $house->id,
                'name' => $house->blok . '/' . $house->nomor,
                'months' => []
            ];

            for ($m = 1; $m <= 12; $m++) {
                $monthDue = $house->dues->filter(function ($d) use ($m) {
                    return \Carbon\Carbon::parse($d->period)->month === $m;
                })->first();

                $data['months'][$m] = [
                    'status' => $monthDue ? $monthDue->status : 'none',
                    'amount' => $monthDue ? $monthDue->amount : 0,
                ];
            }
            return $data;
        });

        return \Inertia\Inertia::render('Warga/Calendar', [
            'calendar' => $calendar,
            'year' => (int)$year,
        ]);
    }
}
