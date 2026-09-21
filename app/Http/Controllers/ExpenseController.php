<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Services\BlokService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function __construct(private BlokService $blok)
    {
    }

    /**
     * Hanya bendahara (admin) & demo yang boleh kelola pengeluaran.
     * Ketua / warga dilarang akses lewat URL langsung.
     */
    private function guardSuperAdmin(): void
    {
        if (!in_array(auth()->user()?->role, ['admin', 'demo'])) {
            abort(403, 'Hanya bendahara yang dapat mengakses halaman pengeluaran.');
        }
    }

    public function index()
    {
        $this->guardSuperAdmin();
        $expenses = Expense::orderBy('date', 'desc')->get();
        
        // Group expenses by Year-Month, e.g., '2026-02' => [...]
        $groupedExpenses = $expenses->groupBy(function($date) {
            return \Carbon\Carbon::parse($date->date)->format('Y-m'); // grouping by year-month
        });

        return Inertia::render('Admin/Expenses/Index', [
            'blokOptions' => $this->blok->expenseOptions(),
            'groupedExpenses' => $groupedExpenses,
            // Still passing raw for flat usage if needed, though grouped is preferred now.
            'expenses' => $expenses, 
        ]);
    }

    public function clone(Request $request)
    {
        $this->guardSuperAdmin();
        $request->validate([
            'source_month' => 'required|date_format:Y-m',
            'target_month' => 'required|date_format:Y-m',
        ]);

        $sourceDate = \Carbon\Carbon::createFromFormat('Y-m', $request->source_month);
        $targetDate = \Carbon\Carbon::createFromFormat('Y-m', $request->target_month);

        $expensesToClone = Expense::whereYear('date', $sourceDate->year)
                                  ->whereMonth('date', $sourceDate->month)
                                  ->get();

        if ($expensesToClone->isEmpty()) {
            return back()->with('error', 'Tidak ada data pengeluaran di bulan sumber yang dipilih.');
        }

        foreach ($expensesToClone as $expense) {
            Expense::create([
                'title' => $expense->title,
                'amount' => $expense->amount,
                // Set the exact same day of the month if possible, or fallback to the 1st
                'date' => $targetDate->copy()->day(min($targetDate->daysInMonth, \Carbon\Carbon::parse($expense->date)->day))->format('Y-m-d'),
                'category' => $expense->category,
                'blok_group' => $expense->blok_group,
                'notes' => $expense->notes,
            ]);
        }

        return back()->with('success', count($expensesToClone) . ' catatan pengeluaran berhasil disalin.');
    }

    /**
     * Tandai ulang banyak pengeluaran sekaligus ke satu blok.
     * Dipakai bendahara untuk merapikan catatan lama tanpa mengedit satu per satu.
     */
    public function bulkBlok(Request $request)
    {
        $this->guardSuperAdmin();
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:expenses,id',
            'blok_group' => 'required|' . $this->blok->expenseGroupRule(),
        ]);

        $updated = Expense::whereIn('id', $validated['ids'])
            ->update(['blok_group' => $validated['blok_group']]);

        $label = $validated['blok_group'] === BlokService::SHARED
            ? 'Bersama'
            : 'Khusus Blok ' . $validated['blok_group'];

        return back()->with('success', $updated . ' pengeluaran ditandai sebagai ' . $label . '.');
    }

    public function store(Request $request)
    {
        $this->guardSuperAdmin();
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'category' => 'nullable|string',
            'blok_group' => 'required|' . $this->blok->expenseGroupRule(),
            'notes' => 'nullable|string',
        ]);

        Expense::create($validated);

        return back()->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function update(Request $request, Expense $expense)
    {
        $this->guardSuperAdmin();
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'category' => 'nullable|string',
            'blok_group' => 'required|' . $this->blok->expenseGroupRule(),
            'notes' => 'nullable|string',
        ]);

        $expense->update($validated);

        return back()->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Expense $expense)
    {
        $this->guardSuperAdmin();
        $expense->delete();
        return back()->with('success', 'Pengeluaran berhasil dihapus.');
    }
}
