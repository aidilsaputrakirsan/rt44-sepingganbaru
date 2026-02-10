<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\MonthlyBalance;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class FinancialReportController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $period = Carbon::createFromDate($year, $month, 1)->startOfMonth();

        // 1. Initial Balance (Saldo Awal)
        $initialBalance = MonthlyBalance::where('period', $period->format('Y-m-d'))->first();

        // 2. Income (Pemasukan)
        $incomeData = Payment::whereHas('due', function ($query) use ($period) {
            $query->where('period', $period->format('Y-m-d'));
        })->where('status', 'verified')->get();

        $totalWajib = $incomeData->sum('amount_wajib');
        $totalSukarela = $incomeData->sum('amount_sukarela');

        // 3. Expenses (Pengeluaran)
        $expenses = Expense::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $totalExpenses = $expenses->sum('amount');

        // 4. Final Calculation
        $saldoAwal = $initialBalance ? $initialBalance->initial_balance : 0;
        $totalPemasukan = $totalWajib + $totalSukarela;
        $saldoAkhir = $saldoAwal + $totalPemasukan - $totalExpenses;

        return Inertia::render('Admin/FinancialReport/Index', [
            'report' => [
                'period_label' => $period->translatedFormat('F Y'),
                'period' => $period->format('Y-m'),
                'saldo_awal' => (float) $saldoAwal,
                'income_wajib' => (float) $totalWajib,
                'income_sukarela' => (float) $totalSukarela,
                'total_income' => (float) $totalPemasukan,
                'expenses' => $expenses,
                'total_expenses' => (float) $totalExpenses,
                'saldo_akhir' => (float) $saldoAkhir,
            ],
            'filters' => [
                'month' => (int) $month,
                'year' => (int) $year,
            ]
        ]);
    }

    public function updateInitialBalance(Request $request)
    {
        $request->validate([
            'period' => 'required|date_format:Y-m',
            'amount' => 'required|numeric',
        ]);

        $period = Carbon::parse($request->period)->startOfMonth()->format('Y-m-d');

        MonthlyBalance::updateOrCreate(
            ['period' => $period],
            ['initial_balance' => $request->amount]
        );

        return back()->with('success', 'Saldo awal berhasil diperbarui.');
    }

    public function exportPdf(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $period = Carbon::createFromDate($year, $month, 1)->startOfMonth();

        // Data gathering logic (same as index)
        $initialBalance = MonthlyBalance::where('period', $period->format('Y-m-d'))->first();
        $incomeData = Payment::whereHas('due', function ($query) use ($period) {
            $query->where('period', $period->format('Y-m-d'));
        })->where('status', 'verified')->get();

        $totalWajib = $incomeData->sum('amount_wajib');
        $totalSukarela = $incomeData->sum('amount_sukarela');
        $expenses = Expense::whereYear('date', $year)->whereMonth('date', $month)->get();
        $totalExpenses = $expenses->sum('amount');

        $saldoAwal = $initialBalance ? $initialBalance->initial_balance : 0;
        $totalPemasukan = $totalWajib + $totalSukarela;
        $saldoAkhir = $saldoAwal + $totalPemasukan - $totalExpenses;

        $data = [
            'report' => [
                'period_label' => $period->translatedFormat('F Y'),
                'period' => $period->format('Y-m'),
                'saldo_awal' => (float) $saldoAwal,
                'income_wajib' => (float) $totalWajib,
                'income_sukarela' => (float) $totalSukarela,
                'total_income' => (float) $totalPemasukan,
                'expenses' => $expenses,
                'total_expenses' => (float) $totalExpenses,
                'saldo_akhir' => (float) $saldoAkhir,
            ]
        ];

        $pdf = Pdf::loadView('reports.financial', $data);
        $filename = 'Laporan_Keuangan_RT44_' . str_replace(' ', '_', $data['report']['period_label']) . '.pdf';

        return $pdf->download($filename);
    }
}
