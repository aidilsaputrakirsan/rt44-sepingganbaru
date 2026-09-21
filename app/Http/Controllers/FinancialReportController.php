<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Services\BlokService;
use App\Models\MonthlyBalance;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class FinancialReportController extends Controller
{
    /** Nilai filter blok yang berarti "seluruh RT". */
    private const BLOK_ALL = BlokService::ALL;

    public function __construct(private BlokService $blok)
    {
    }

    private function guardSuperAdmin(): void
    {
        if (!in_array(auth()->user()?->role, ['admin', 'demo'])) {
            abort(403, 'Hanya bendahara yang dapat mengakses laporan keuangan.');
        }
    }

    public function index(Request $request)
    {
        $this->guardSuperAdmin();
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $blok = $this->blok->normalizeFilter($request->input('blok'));

        return Inertia::render('Admin/FinancialReport/Index', [
            'report' => $this->getReportData((int) $month, (int) $year, $blok),
            'filters' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'blok' => $blok,
            ]
        ]);
    }

    public function updateInitialBalance(Request $request)
    {
        $this->guardSuperAdmin();
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
        $this->guardSuperAdmin();
        $request->validate([
            'period' => 'required|date_format:Y-m',
        ]);

        $period = Carbon::parse($request->period)->startOfMonth()->format('Y-m-d');

        MonthlyBalance::where('period', $period)->delete();

        return back()->with('success', 'Saldo awal dikembalikan ke mode otomatis.');
    }

    public function exportPdf(Request $request)
    {
        $this->guardSuperAdmin();
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $blok = $this->blok->normalizeFilter($request->input('blok'));

        $data = ['report' => $this->getReportData((int) $month, (int) $year, $blok)];

        $pdf = Pdf::loadView('reports.financial', $data);
        $suffix = $blok === self::BLOK_ALL ? '' : '_Blok_' . $blok;
        $filename = 'Laporan_Keuangan_RT44' . $suffix . '_' . str_replace(' ', '_', $data['report']['period_label']) . '.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Ubah koleksi Expense jadi baris laporan, lengkap dengan porsi blok
     * yang sedang dilihat.
     */
    private function mapExpenses($expenses, string $blok): array
    {
        $counts = $this->blok->counts();
        $totalHouses = array_sum($counts);

        return $expenses
            ->map(function (Expense $e) use ($blok, $counts, $totalHouses) {
                $group = $e->blok_group ?: BlokService::SHARED;
                $full = (float) $e->amount;
                $isShared = $group === BlokService::SHARED;

                if ($blok === self::BLOK_ALL) {
                    $allocated = $full;
                } elseif ($isShared) {
                    $allocated = $this->blok->splitShared($full)[$blok] ?? 0.0;
                } else {
                    $allocated = $group === $blok ? $full : 0.0;
                }

                return [
                    'id'            => $e->id,
                    'title'         => $e->title,
                    'category'      => $e->category,
                    'date'          => Carbon::parse($e->date)->format('Y-m-d'),
                    'blok_group'    => $group,
                    'is_shared'     => $isShared,
                    'amount'        => $allocated,   // nominal yang dipakai di laporan ini
                    'amount_full'   => $full,        // nominal utuh di nota
                    'share_percent' => $isShared && $blok !== self::BLOK_ALL && $totalHouses > 0
                        ? round(($counts[$blok] ?? 0) / $totalHouses * 100, 1)
                        : null,
                ];
            })
            // Pengeluaran milik blok lain tidak perlu muncul di laporan blok ini.
            ->filter(fn ($row) => $blok === self::BLOK_ALL || $row['amount'] > 0)
            ->values()
            ->all();
    }

    /**
     * Build the financial report data array for a given month/year.
     */
    private function getReportData(int $month, int $year, string $blok = self::BLOK_ALL): array
    {
        // Nama bulan/tanggal dalam bahasa Indonesia untuk tampilan & PDF
        Carbon::setLocale('id');

        $period = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $isBlokView = $blok !== self::BLOK_ALL;
        $counts = $this->blok->counts();
        $totalHouses = array_sum($counts);

        // Huruf blok sebuah rumah, dipakai untuk memilah pemasukan.
        $blokOf = fn ($house) => $this->blok->letterOf($house);

        // Cek apakah ada saldo awal manual untuk bulan ini
        $manualBalance = MonthlyBalance::where('period', $period->format('Y-m-d'))->first();
        $isManualSaldo = $manualBalance !== null;

        if ($isManualSaldo) {
            $saldoAwal = (float) $manualBalance->initial_balance;
        } else {
            // Otomatis: saldo akhir bulan sebelumnya
            $saldoAwal = $this->calculateSaldoAkhir($period->copy()->subMonth());
        }

        // === Basis Tanggal Bayar (Lump Sum) ===
        $incomeData = Payment::with(['due.house'])
            ->where('status', 'verified')
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->get();

        // Simpan versi lengkap dulu — dipakai untuk ringkasan per blok.
        $allIncomeData = $incomeData;

        if ($isBlokView) {
            $incomeData = $incomeData->filter(fn ($p) => $blokOf($p->due?->house) === $blok)->values();
        }

        $totalWajib = $incomeData->sum('amount_wajib');
        $totalSukarela = $incomeData->sum('amount_sukarela');

        $sortPayment = function ($p) {
            $house = $p->due?->house;
            if (!$house) return 'ZZZ';
            preg_match('/^([A-Za-z]+)(\d*)/', $house->blok, $m);
            return ($m[1] ?? '') . str_pad($m[2] ?? '0', 5, '0', STR_PAD_LEFT) . str_pad($house->nomor, 5, '0', STR_PAD_LEFT);
        };

        $mapPayment = function ($p) use ($blokOf) {
            $house = $p->due?->house;
            return [
                'rumah'           => $house ? $house->blok . '/' . $house->nomor : '—',
                'blok'            => $blokOf($house),
                'period'          => $p->due ? Carbon::parse($p->due->period)->translatedFormat('M Y') : '—',
                'payment_date'    => Carbon::parse($p->payment_date)->isoFormat('D MMM Y'),
                'amount_wajib'    => (float) $p->amount_wajib,
                'amount_sukarela' => (float) $p->amount_sukarela,
                'total'           => (float) $p->amount_wajib + (float) $p->amount_sukarela,
                'notes'           => $p->notes ?? '',
            ];
        };

        $incomeBreakdown = $incomeData->sortBy($sortPayment)->map($mapPayment)->values();

        // === Basis Periode Tagihan (Non-Lump Sum) ===
        // Pemasukan dihitung dari pembayaran yang due-nya jatuh pada periode ini
        $periodIncomeData = Payment::with(['due.house'])
            ->where('status', 'verified')
            ->whereHas('due', function ($q) use ($period) {
                $q->whereYear('period', $period->year)
                  ->whereMonth('period', $period->month);
            })
            ->get();

        if ($isBlokView) {
            $periodIncomeData = $periodIncomeData->filter(fn ($p) => $blokOf($p->due?->house) === $blok)->values();
        }

        $periodWajib    = $periodIncomeData->sum('amount_wajib');
        $periodSukarela = $periodIncomeData->sum('amount_sukarela');
        $periodTotal    = $periodWajib + $periodSukarela;

        $periodBreakdown = $periodIncomeData->sortBy($sortPayment)->map($mapPayment)->values();

        // === Pengeluaran ===
        $expenseModels = Expense::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();

        $expenses = $this->mapExpenses($expenseModels, $blok);
        $totalExpenses = array_sum(array_column($expenses, 'amount'));

        $expensesDirect = array_sum(array_map(
            fn ($e) => $e['is_shared'] ? 0 : $e['amount'],
            $expenses
        ));
        $expensesShared = $totalExpenses - $expensesDirect;

        $totalPemasukan = $totalWajib + $totalSukarela;
        $saldoAkhir     = $saldoAwal + $totalPemasukan - $totalExpenses;

        // === Ringkasan per blok (hanya di tampilan gabungan) ===
        $blokSummary = [];
        if (!$isBlokView) {
            foreach (array_keys($counts) as $g) {
                $gIncome = $allIncomeData->filter(fn ($p) => $blokOf($p->due?->house) === $g);
                $gExpenses = $this->mapExpenses($expenseModels, $g);
                $gTotalExpense = array_sum(array_column($gExpenses, 'amount'));
                $gTotalIncome = (float) ($gIncome->sum('amount_wajib') + $gIncome->sum('amount_sukarela'));

                $blokSummary[] = [
                    'blok'         => $g,
                    'label'        => 'Blok ' . $g,
                    'house_count'  => $counts[$g],
                    'share_percent' => $totalHouses > 0 ? round($counts[$g] / $totalHouses * 100, 1) : 0,
                    'income'       => $gTotalIncome,
                    'expenses'     => $gTotalExpense,
                    'net'          => $gTotalIncome - $gTotalExpense,
                ];
            }
        }

        return [
            'period_label'           => $period->translatedFormat('F Y'),
            'period'                 => $period->format('Y-m'),
            // Filter blok
            'blok'                   => $blok,
            'blok_label'             => $isBlokView ? 'Blok ' . $blok : 'Seluruh RT-44',
            'is_blok_view'           => $isBlokView,
            'house_counts'           => $counts,
            'total_houses'           => $totalHouses,
            'house_count'            => $isBlokView ? ($counts[$blok] ?? 0) : $totalHouses,
            'share_percent'          => $isBlokView && $totalHouses > 0
                ? round(($counts[$blok] ?? 0) / $totalHouses * 100, 1)
                : 100,
            'blok_summary'           => $blokSummary,
            // Saldo kas hanya bermakna untuk seluruh RT (kas RT tetap satu)
            'saldo_awal'             => $isBlokView ? null : $saldoAwal,
            'saldo_akhir'            => $isBlokView ? null : (float) $saldoAkhir,
            'is_manual_saldo'        => $isManualSaldo,
            // Lump sum (basis tanggal bayar)
            'income_wajib'           => (float) $totalWajib,
            'income_sukarela'        => (float) $totalSukarela,
            'total_income'           => (float) $totalPemasukan,
            'income_breakdown'       => $incomeBreakdown,
            // Per periode (basis periode tagihan)
            'income_wajib_period'    => (float) $periodWajib,
            'income_sukarela_period' => (float) $periodSukarela,
            'total_income_period'    => (float) $periodTotal,
            'selisih_period'         => (float) ($periodTotal - $totalExpenses),
            'income_breakdown_period'=> $periodBreakdown,
            // Shared
            'expenses'               => $expenses,
            'total_expenses'         => (float) $totalExpenses,
            'expenses_direct'        => (float) $expensesDirect,
            'expenses_shared'        => (float) $expensesShared,
            'net'                    => (float) ($totalPemasukan - $totalExpenses),
        ];
    }

    /**
     * Hitung saldo akhir untuk bulan tertentu (chain dari bulan sebelumnya).
     * Selalu tingkat RT — kas RT tidak dipecah per blok.
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
