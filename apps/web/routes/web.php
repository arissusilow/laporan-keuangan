<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApplicationIdentityController;
use App\Http\Controllers\ApplicationSettingController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\PeriodReportController;
use App\Http\Controllers\PublicReportPdfController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SlideController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok', 'mode' => 'backend']))->name('health');
Route::get('/identitas/logo', [ApplicationIdentityController::class, 'logo'])->name('identity.logo');
Route::get('/slide/{token}/background', [SlideController::class, 'background'])->name('slides.background');
Route::get('/slide/{token}/laporan-lengkap.pdf', PublicReportPdfController::class)
    ->middleware('throttle:10,1')
    ->name('slides.pdf');
Route::get('/slide/{token}', [SlideController::class, 'show'])->name('slides.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/lupa-kata-sandi', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/lupa-kata-sandi', [ForgotPasswordController::class, 'send'])
        ->middleware('throttle:3,1')
        ->name('password.email');
    Route::get('/reset-kata-sandi/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-kata-sandi', [ResetPasswordController::class, 'update'])->name('password.update');
});

Route::middleware(['auth', 'user.active'])->group(function (): void {
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
    Route::get('/ganti-kata-sandi-awal', [AuthController::class, 'firstPassword'])->name('password.first');
    Route::put('/ganti-kata-sandi-awal', [AuthController::class, 'updateFirstPassword'])->name('password.first.update');

    Route::middleware('password.changed')->group(function (): void {
        Route::get('/', fn () => redirect()->route('reports.index'));
        Route::resource('laporan', ReportController::class)
            ->parameters(['laporan' => 'report'])
            ->names('reports')
            ->except(['show', 'destroy', 'edit']);
        Route::get('/laporan/{report}/dashboard', [ReportController::class, 'dashboard'])->name('reports.dashboard');
        Route::get('/laporan/{report}/pengaturan', [ReportController::class, 'settings'])->name('reports.settings');
        Route::put('/laporan/{report}/pengaturan/pdf', [ReportController::class, 'updatePdfSettings'])->name('reports.pdf-settings.update');
        Route::get('/laporan/{report}/slide', [SlideController::class, 'preview'])->name('slides.preview');
        Route::get('/laporan/{report}/slide/background', [SlideController::class, 'previewBackground'])->name('slides.preview.background');

        Route::get('/laporan/{report}/transaksi', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/laporan/{report}/transaksi/baru/{type?}', [TransactionController::class, 'create'])->name('transactions.create');
        Route::post('/laporan/{report}/transaksi/baru/{type}', [TransactionController::class, 'store'])->name('transactions.store');
        Route::get('/laporan/{report}/transaksi/{transaction}', [TransactionController::class, 'show'])->scopeBindings()->name('transactions.show');
        Route::get('/laporan/{report}/transaksi/{transaction}/ubah', [TransactionController::class, 'edit'])->scopeBindings()->name('transactions.edit');
        Route::put('/laporan/{report}/transaksi/{transaction}', [TransactionController::class, 'update'])->scopeBindings()->name('transactions.update');
        Route::post('/laporan/{report}/transaksi/{transaction}/batalkan', [TransactionController::class, 'cancel'])->scopeBindings()->name('transactions.cancel');

        Route::get('/laporan/{report}/periode', [PeriodReportController::class, 'show'])->name('period.show');
        Route::post('/laporan/{report}/periode/pdf', [PeriodReportController::class, 'export'])->name('period.export');
        Route::get('/pdf/{export}', [PeriodReportController::class, 'download'])->name('pdf.download');
        Route::get('/lampiran/{attachment}', [AttachmentController::class, 'download'])->name('attachments.download');

        Route::post('/laporan/{report}/kategori', [AdminController::class, 'categoryStore'])->name('categories.store');
        Route::put('/laporan/{report}/kategori/{category}', [AdminController::class, 'categoryUpdate'])->scopeBindings()->name('categories.update');
        Route::delete('/laporan/{report}/kategori/{category}', [AdminController::class, 'categoryDestroy'])->scopeBindings()->name('categories.destroy');
        Route::post('/laporan/{report}/anggota', [AdminController::class, 'memberStore'])->name('members.store');
        Route::put('/laporan/{report}/anggota/{member}', [AdminController::class, 'memberUpdate'])->scopeBindings()->name('members.update');
        Route::delete('/laporan/{report}/anggota/{member}', [AdminController::class, 'memberDestroy'])->scopeBindings()->name('members.destroy');
        Route::put('/laporan/{report}/slide', [AdminController::class, 'slideUpdate'])->name('slides.update');

        Route::get('/admin/sistem', [AdminController::class, 'system'])->name('admin.system');
        Route::get('/admin/konfigurasi', [ApplicationSettingController::class, 'index'])->name('admin.settings.index');
        Route::put('/admin/konfigurasi', [ApplicationSettingController::class, 'update'])->name('admin.settings.update');
        Route::post('/admin/pengguna', [AdminController::class, 'userStore'])->name('admin.users.store');
        Route::put('/admin/pengguna/{user}', [AdminController::class, 'userUpdate'])->name('admin.users.update');
        Route::post('/admin/pengguna/{user}/reset-kata-sandi', [AdminController::class, 'userPasswordReset'])->middleware('throttle:3,1')->name('admin.users.password-reset');
        Route::post('/admin/backup', [BackupController::class, 'store'])->name('admin.backups.store');
        Route::put('/admin/backup/pengaturan', [BackupController::class, 'updateSettings'])->name('admin.backups.settings');
    });
});
