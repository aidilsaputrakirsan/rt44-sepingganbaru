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

        return Inertia::render('Admin/FinancialReport/Index', [
            'report' => $this->getReportData((int) $month, (int) $year),
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

    public function deleteInitialBalance(Request $request)
    {
        $request->validate([
            'period' => 'required|date_format:Y-m',
        ]);

        $period = Carbon::parse($request->period)->startOfMonth()->format('Y-m-d');

        MonthlyBalance::where('period', $period)->delete();

        return back()->with('success', 'Saldo awal dikembalikan ke mode otomatis.');
    }

    public function exportPdf(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $data = ['report' => $this->getReportData((int) $month, (int) $year)];

        $pdf = Pdf::loadView('reports.financial', $data);
        $filename = 'Laporan_Keuangan_RT44_' . str_replace(' ', '_', $data['report']['period_label']) . '.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Build the financial report data array for a given month/year.
     */
    private function getReportData(int $month, int $year): array
    {
        $period = Carbon::createFromDate($year, $month, 1)->startOfMonth();

        // Cek apakah ada saldo awal manual untuk bulan ini
        $manualBalance = MonthlyBalance::where('period', $period->format('Y-m-d'))->first();
        $isManualSaldo = $manualBalance !== null;

        if ($isManualSaldo) {
            $saldoAwal = (float) $manualBalance->initial_balance;
        } else {
            // Otomatis: saldo akhir bulan sebelumnya
            $saldoAwal = $this->calculateSaldoAkhir($period->copy()->subMonth());
        }

        $incomeData = Payment::where('status', 'verified')
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->get();

        $totalWajib = $incomeData->sum('amount_wajib');
        $totalSukarela = $incomeData->sum('amount_sukarela');

        $expenses = Expense::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $totalExpenses = $expenses->sum('amount');

        $totalPemasukan = $totalWajib + $totalSukarela;
        $saldoAkhir = $saldoAwal + $totalPemasukan - $totalExpenses;

        return [
            'period_label' => $period->translatedFormat('F Y'),
            'period' => $period->format('Y-m'),
            'saldo_awal' => $saldoAwal,
            'is_manual_saldo' => $isManualSaldo,
            'income_wajib' => (float) $totalWajib,
            'income_sukarela' => (float) $totalSukarela,
            'total_income' => (float) $totalPemasukan,
            'expenses' => $expenses,
            'total_expenses' => (float) $totalExpenses,
            'saldo_akhir' => (float) $saldoAkhir,
        ];
    }

    /**
     * Hitung saldo akhir untuk bulan tertentu (chain dari bulan sebelumnya).
     */
    private function calculateSaldoAkhir(Carbon $period): float
    {
        // Cek apakah bulan ini punya saldo awal manual → pakai sebagai anchor
        $manualBalance = MonthlyBalance::where('period', $period->format('Y-m-d'))->first();

        if ($manualBalance) {
            $saldoAwal = (float) $manualBalance->initial_balance;
        } else {
            // Batas bawah: jangan chain tanpa batas, stop di awal tahun data atau return 0
            $earliestPayment = Payment::where('status', 'verified')->min('payment_date');
            $earliestExpense = Expense::min('date');
            $earliest = min($earliestPayment ?? '9999-12-31', $earliestExpense ?? '9999-12-31');

            if ($earliest === '9999-12-31' || $period->lt(Carbon::parse($earliest)->startOfMonth())) {
                return 0;
            }

            // Rekursif ke bulan sebelumnya
            $saldoAwal = $this->calculateSaldoAkhir($period->copy()->subMonth());
        }

        // Hitung pemasukan & pengeluaran bulan ini
        $income = Payment::where('status', 'verified')
            ->whereYear('payment_date', $period->year)
            ->whereMonth('payment_date', $period->month)
            ->selectRaw('COALESCE(SUM(amount_wajib), 0) + COALESCE(SUM(amount_sukarela), 0) as total')
            ->value('total') ?? 0;

        $expenses = Expense::whereYear('date', $period->year)
            ->whereMonth('date', $period->month)
            ->sum('amount') ?? 0;

        return (float) ($saldoAwal + $income - $expenses);
    }
}

