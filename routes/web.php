<?php

use App\Http\Controllers\BrandingController;
use App\Http\Controllers\NotulenController;
use App\Http\Controllers\PushSubscriptionController;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
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

Route::middleware([Authenticate::class])->group(function () {
    Route::get('/notulen/view/{id}', [NotulenController::class, 'view'])->name('notulen.view');

    // Serve uploaded document files directly from storage disk (not relying on symlink)
    Route::get('/storage/documents/{path}', function (string $path) {
        $disk = \Illuminate\Support\Facades\Storage::disk('documents');
        if (! $disk->exists($path)) {
            abort(404);
        }
        $mimeType = $disk->mimeType($path) ?: 'application/octet-stream';

        return response($disk->get($path), 200)->header('Content-Type', $mimeType);
    })->where('path', '.*')->name('documents.serve');
});

Route::get('/test-session', function () {
    session(['test' => 'ok']);

    return session('test');
});

Route::get('/test-livewire', function () {
    return 'ok';
});
