<?php

use App\Http\Controllers\AgendaNoticeController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\DocumentFileController;
use App\Http\Controllers\NotulenController;
use App\Http\Controllers\PushSubscriptionController;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Login SSO yang gagal dikembalikan package ke "/" dengan pesan error —
    // teruskan langsung ke halaman login supaya pesannya tidak hilang di redirect berikutnya.
    if (session()->has('errors')) {
        session()->reflash();

        return redirect()->route('filament.admin.auth.login');
    }

    return redirect('/admin');
});

// Logo aplikasi — publik (dipakai di favicon & halaman login sebelum auth).
// Disajikan langsung dari disk, tidak bergantung symlink public/storage.
Route::get('/branding/logo', [BrandingController::class, 'logo'])->name('branding.logo');
Route::get('/branding/logo/preview', [BrandingController::class, 'preview'])->name('branding.preview');

Route::get('/api/push-subscriptions/vapid-key', [PushSubscriptionController::class, 'vapidPublicKey'])->name('push.vapid');

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/api/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push.store');
    Route::post('/api/push-subscriptions/send-test', [PushSubscriptionController::class, 'sendTestNotification'])->name('push.send-test');
    Route::delete('/api/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push.destroy');
});

// File dokumen untuk API integrasi — link bertanda tangan & berbatas waktu (tanpa login).
Route::get('/dokumen/file/{path}', [DocumentFileController::class, 'signed'])
    ->where('path', '.*')
    ->middleware('signed')
    ->name('documents.serve.signed');

// Surat pemberitahuan kegiatan — link bertanda tangan untuk n8n / grup WhatsApp (tanpa login).
Route::get('/kegiatan/{agenda}/surat/unduh', [AgendaNoticeController::class, 'signed'])
    ->middleware('signed')
    ->name('kegiatan.surat.signed');

Route::middleware([Authenticate::class])->group(function () {
    Route::get('/notulen/view/{id}', [NotulenController::class, 'view'])->name('notulen.view');
    Route::get('/kegiatan/{agenda}/surat', [AgendaNoticeController::class, 'show'])->name('kegiatan.surat');

    // File dokumen resmi (disk privat) — dicek login + hak akses dokumen
    Route::get('/storage/documents/{path}', [DocumentFileController::class, 'show'])
        ->where('path', '.*')
        ->name('documents.serve');
});
