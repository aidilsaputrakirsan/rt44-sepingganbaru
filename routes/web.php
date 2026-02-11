<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\AdminController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

use App\Http\Controllers\DashboardController;

// ...

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/dashboard/calendar', [DashboardController::class, 'calendar'])->middleware(['auth', 'verified'])->name('dashboard.calendar');
Route::post('/dues/{due}/pay', [\App\Http\Controllers\PaymentController::class, 'store'])->middleware(['auth', 'verified'])->name('payment.store');
Route::get('/payments/{payment}/receipt', [\App\Http\Controllers\PaymentController::class, 'receipt'])->middleware(['auth', 'verified'])->name('payment.receipt');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/tagihan', [AdminController::class, 'tagihan'])->name('admin.tagihan');
    Route::get('/admin/calendar', [AdminController::class, 'calendar'])->name('admin.calendar');
    Route::get('/admin/calendar/export-pdf', [AdminController::class, 'exportCalendarPdf'])->name('admin.calendar.export-pdf');
    Route::post('/admin/payment/{due}', [AdminController::class, 'storePayment'])->name('admin.payment.store');
    Route::patch('/admin/due/{due}', [AdminController::class, 'updateDue'])->name('admin.due.update');
    Route::post('/admin/tagihan/bulk-update', [AdminController::class, 'bulkUpdateDues'])->name('admin.tagihan.bulk-update');
    Route::get('/admin/reminder/{house}/preview', [AdminController::class, 'previewReminder'])->name('admin.reminder.preview');
    Route::post('/admin/reminder/{house}', [AdminController::class, 'sendReminder'])->name('admin.reminder.send');

    // Warga (Resident) Data Routes
    Route::get('/admin/warga', [\App\Http\Controllers\WargaController::class, 'index'])->name('admin.warga.index');
    Route::post('/admin/warga', [\App\Http\Controllers\WargaController::class, 'store'])->name('admin.warga.store');
    Route::put('/admin/warga/{house}', [\App\Http\Controllers\WargaController::class, 'update'])->name('admin.warga.update');
    Route::delete('/admin/warga/{house}', [\App\Http\Controllers\WargaController::class, 'destroy'])->name('admin.warga.destroy');
    Route::post('/admin/warga/import', [\App\Http\Controllers\WargaController::class, 'import'])->name('admin.warga.import');
    Route::get('/admin/warga/download-template', [\App\Http\Controllers\WargaController::class, 'downloadTemplate'])->name('admin.warga.template');

    // Expense Routes
    Route::get('/admin/expenses', [\App\Http\Controllers\ExpenseController::class, 'index'])->name('admin.expenses.index');
    Route::post('/admin/expenses', [\App\Http\Controllers\ExpenseController::class, 'store'])->name('admin.expenses.store');
    Route::delete('/admin/expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'destroy'])->name('admin.expenses.destroy');

    // Financial Report Routes
    Route::get('/admin/report', [\App\Http\Controllers\FinancialReportController::class, 'index'])->name('admin.report.index');
    Route::post('/admin/report/initial-balance', [\App\Http\Controllers\FinancialReportController::class, 'updateInitialBalance'])->name('admin.report.initial-balance');
    Route::get('/admin/report/export-pdf', [\App\Http\Controllers\FinancialReportController::class, 'exportPdf'])->name('admin.report.export-pdf');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
