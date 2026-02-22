<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::orderBy('date', 'desc')->get();
        
        // Group expenses by Year-Month, e.g., '2026-02' => [...]
        $groupedExpenses = $expenses->groupBy(function($date) {
            return \Carbon\Carbon::parse($date->date)->format('Y-m'); // grouping by year-month
        });

        return Inertia::render('Admin/Expenses/Index', [
            'groupedExpenses' => $groupedExpenses,
            // Still passing raw for flat usage if needed, though grouped is preferred now.
            'expenses' => $expenses, 
        ]);
    }

    public function clone(Request $request)
    {
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
                'notes' => $expense->notes,
            ]);
        }

        return back()->with('success', count($expensesToClone) . ' catatan pengeluaran berhasil disalin.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'category' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        Expense::create($validated);

        return back()->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'category' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $expense->update($validated);

        return back()->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return back()->with('success', 'Pengeluaran berhasil dihapus.');
    }
}
