<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\AdminController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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

// Profil Warga (warga side)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profil', [\App\Http\Controllers\ResidentProfileController::class, 'show'])->name('profil.show');
    Route::post('/profil', [\App\Http\Controllers\ResidentProfileController::class, 'update'])->name('profil.update');
    Route::post('/profil/kk', [\App\Http\Controllers\ResidentProfileController::class, 'uploadKk'])->name('profil.kk.upload');
    Route::delete('/profil/kk', [\App\Http\Controllers\ResidentProfileController::class, 'deleteKk'])->name('profil.kk.delete');
    Route::post('/profil/ktp', [\App\Http\Controllers\ResidentProfileController::class, 'uploadKtp'])->name('profil.ktp.upload');
    Route::put('/profil/ktp/{idCard}', [\App\Http\Controllers\ResidentProfileController::class, 'updateKtp'])->name('profil.ktp.update');
    Route::delete('/profil/ktp/{idCard}', [\App\Http\Controllers\ResidentProfileController::class, 'deleteKtp'])->name('profil.ktp.delete');
});

// Papan Informasi (semua role bisa lihat, admin & ketua bisa upload/hapus)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/info', [\App\Http\Controllers\InfoController::class, 'index'])->name('info.index');
    Route::post('/info', [\App\Http\Controllers\InfoController::class, 'store'])->name('info.store');
    Route::delete('/info/{info}', [\App\Http\Controllers\InfoController::class, 'destroy'])->name('info.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/tagihan', [AdminController::class, 'tagihan'])->name('admin.tagihan');
    Route::get('/admin/calendar', [AdminController::class, 'calendar'])->name('admin.calendar');
    Route::get('/admin/calendar/export-pdf', [AdminController::class, 'exportCalendarPdf'])->name('admin.calendar.export-pdf');
    Route::get('/admin/kartu/{house}', [AdminController::class, 'kartuPreview'])->name('admin.kartu');
    Route::post('/admin/payment/house/{house}', [AdminController::class, 'storeLumpSumPayment'])->name('admin.payment.lump-sum');
    Route::post('/admin/payment/{due}', [AdminController::class, 'storePayment'])->name('admin.payment.store');
    Route::patch('/admin/due/{due}', [AdminController::class, 'updateDue'])->name('admin.due.update');
    Route::post('/admin/tagihan/bulk-update', [AdminController::class, 'bulkUpdateDues'])->name('admin.tagihan.bulk-update');
    Route::get('/admin/reminder/{house}/preview', [AdminController::class, 'previewReminder'])->name('admin.reminder.preview');
    Route::post('/admin/reminder/{house}', [AdminController::class, 'sendReminder'])->name('admin.reminder.send');
    Route::post('/admin/settings/toggle-auto-reminder', [AdminController::class, 'toggleAutoReminder'])->name('admin.settings.toggle-auto-reminder');

    // Warga (Resident) Data Routes
    Route::get('/admin/warga', [\App\Http\Controllers\WargaController::class, 'index'])->name('admin.warga.index');
    Route::post('/admin/warga', [\App\Http\Controllers\WargaController::class, 'store'])->name('admin.warga.store');
    Route::put('/admin/warga/{house}', [\App\Http\Controllers\WargaController::class, 'update'])->name('admin.warga.update');
    Route::post('/admin/warga/{house}/recalculate', [\App\Http\Controllers\WargaController::class, 'recalculateDues'])->name('admin.warga.recalculate');
    Route::post('/admin/warga/{house}/tenant', [\App\Http\Controllers\WargaController::class, 'storeTenant'])->name('admin.warga.tenant.store');
    Route::put('/admin/warga/{house}/tenant', [\App\Http\Controllers\WargaController::class, 'updateTenant'])->name('admin.warga.tenant.update');
    Route::delete('/admin/warga/{house}/tenant', [\App\Http\Controllers\WargaController::class, 'destroyTenant'])->name('admin.warga.tenant.destroy');
    Route::get('/admin/warga/{house}/koreksi-tagihan', [\App\Http\Controllers\WargaController::class, 'duesForKoreksi'])->name('admin.warga.koreksi.dues');
    Route::post('/admin/warga/{house}/koreksi-tagihan', [\App\Http\Controllers\WargaController::class, 'koreksiTagihan'])->name('admin.warga.koreksi.submit');
    Route::delete('/admin/warga/{house}', [\App\Http\Controllers\WargaController::class, 'destroy'])->name('admin.warga.destroy');
    Route::post('/admin/warga/import', [\App\Http\Controllers\WargaController::class, 'import'])->name('admin.warga.import');
    Route::get('/admin/warga/download-template', [\App\Http\Controllers\WargaController::class, 'downloadTemplate'])->name('admin.warga.template');
    Route::get('/admin/warga/export-status-pdf', [\App\Http\Controllers\WargaController::class, 'exportStatusPdf'])->name('admin.warga.export-status-pdf');
    Route::get('/admin/warga/export-excel', [\App\Http\Controllers\WargaController::class, 'exportExcel'])->name('admin.warga.export-excel');
    Route::get('/admin/warga/{house}/profil', [\App\Http\Controllers\ResidentProfileController::class, 'adminShow'])->name('admin.warga.profil');
    Route::put('/admin/warga/{house}/profil', [\App\Http\Controllers\ResidentProfileController::class, 'adminUpdate'])->name('admin.warga.profil.update');
    Route::post('/admin/warga/{house}/profil/kk', [\App\Http\Controllers\ResidentProfileController::class, 'adminUploadKk'])->name('admin.warga.profil.kk.upload');
    Route::delete('/admin/warga/{house}/profil/kk', [\App\Http\Controllers\ResidentProfileController::class, 'adminDeleteKk'])->name('admin.warga.profil.kk.delete');
    Route::post('/admin/warga/{house}/profil/ktp', [\App\Http\Controllers\ResidentProfileController::class, 'adminUploadKtp'])->name('admin.warga.profil.ktp.upload');
    Route::put('/admin/warga/{house}/profil/ktp/{idCard}', [\App\Http\Controllers\ResidentProfileController::class, 'adminUpdateKtp'])->name('admin.warga.profil.ktp.update');
    Route::delete('/admin/warga/{house}/profil/ktp/{idCard}', [\App\Http\Controllers\ResidentProfileController::class, 'adminDeleteKtp'])->name('admin.warga.profil.ktp.delete');

    // Expense Routes
    Route::get('/admin/expenses', [\App\Http\Controllers\ExpenseController::class, 'index'])->name('admin.expenses.index');
    Route::post('/admin/expenses/clone', [\App\Http\Controllers\ExpenseController::class, 'clone'])->name('admin.expenses.clone');
    Route::post('/admin/expenses', [\App\Http\Controllers\ExpenseController::class, 'store'])->name('admin.expenses.store');
    Route::put('/admin/expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'update'])->name('admin.expenses.update');
    Route::delete('/admin/expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'destroy'])->name('admin.expenses.destroy');

    // Financial Report Routes
    Route::get('/admin/report', [\App\Http\Controllers\FinancialReportController::class, 'index'])->name('admin.report.index');
    Route::post('/admin/report/initial-balance', [\App\Http\Controllers\FinancialReportController::class, 'updateInitialBalance'])->name('admin.report.initial-balance');
    Route::delete('/admin/report/initial-balance', [\App\Http\Controllers\FinancialReportController::class, 'deleteInitialBalance'])->name('admin.report.delete-initial-balance');
    Route::get('/admin/report/export-pdf', [\App\Http\Controllers\FinancialReportController::class, 'exportPdf'])->name('admin.report.export-pdf');
});

// Super Admin — kelola akun admin & ketua (khusus role superadmin)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/superadmin/akun', [\App\Http\Controllers\SuperAdminController::class, 'index'])->name('superadmin.akun.index');
    Route::put('/superadmin/akun/{user}/password', [\App\Http\Controllers\SuperAdminController::class, 'updatePassword'])->name('superadmin.akun.password');
    Route::put('/superadmin/akun/{user}/profile', [\App\Http\Controllers\SuperAdminController::class, 'updateProfile'])->name('superadmin.akun.profile');
});

// Demografi / Statistik Kependudukan (khusus Ketua)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/ketua/demografi', [\App\Http\Controllers\DemografiController::class, 'index'])->name('ketua.demografi.index');

    // Pemantauan Stunting / Gizi Balita (khusus Ketua)
    Route::get('/ketua/stunting', [\App\Http\Controllers\StuntingController::class, 'index'])->name('ketua.stunting.index');
    Route::get('/ketua/stunting/template', [\App\Http\Controllers\StuntingController::class, 'template'])->name('ketua.stunting.template');
    Route::post('/ketua/stunting/import', [\App\Http\Controllers\StuntingController::class, 'import'])->name('ketua.stunting.import');
    Route::post('/ketua/stunting/{idCard}/ukur', [\App\Http\Controllers\StuntingController::class, 'store'])->name('ketua.stunting.store');
    Route::delete('/ketua/stunting/ukur/{measurement}', [\App\Http\Controllers\StuntingController::class, 'destroy'])->name('ketua.stunting.destroy');
});

// Surat Pengantar (ketua, admin, demo)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/ketua/surat-pengantar', [\App\Http\Controllers\SuratPengantarController::class, 'index'])->name('ketua.surat-pengantar.index');
    Route::post('/ketua/surat-pengantar/generate', [\App\Http\Controllers\SuratPengantarController::class, 'generate'])->name('ketua.surat-pengantar.generate');
    Route::get('/ketua/surat-pengantar/{letterNumber}/pdf', [\App\Http\Controllers\SuratPengantarController::class, 'reprint'])->name('ketua.surat-pengantar.reprint');

    // Agenda Surat (buku nomor surat keluar)
    Route::get('/ketua/agenda-surat', [\App\Http\Controllers\LetterNumberController::class, 'index'])->name('ketua.agenda-surat.index');
    Route::post('/ketua/agenda-surat', [\App\Http\Controllers\LetterNumberController::class, 'store'])->name('ketua.agenda-surat.store');
    Route::put('/ketua/agenda-surat/{letterNumber}', [\App\Http\Controllers\LetterNumberController::class, 'update'])->name('ketua.agenda-surat.update');
    Route::delete('/ketua/agenda-surat/{letterNumber}', [\App\Http\Controllers\LetterNumberController::class, 'destroy'])->name('ketua.agenda-surat.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Demo auto-login route
Route::get('/demo', function () {
    $demoUser = User::where('email', 'demo@rt44.com')->first();

    if (!$demoUser) {
        return redirect('/')->with('error', 'Akun demo belum tersedia.');
    }

    Auth::login($demoUser);
    request()->session()->regenerate();

    return redirect()->route('admin.dashboard');
})->name('demo');

require __DIR__ . '/auth.php';
