<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    /**
     * Dapatkan Public VAPID Key untuk client-side JS.
     */
    public function vapidPublicKey(): JsonResponse
    {
        return response()->json([
            'publicKey' => config('webpush.vapid.public_key'),
        ]);
    }

    /**
     * Simpan / Perbarui PushSubscription milik pengguna yang sedang terautentikasi.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string',
            'keys.auth' => 'required|string',
            'keys.p256dh' => 'required|string',
        ]);

        $user = Auth::user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $endpoint = $request->endpoint;
        $key = $request->input('keys.p256dh');
        $token = $request->input('keys.auth');
        $contentEncoding = $request->input('contentEncoding', 'aesgcm');

        $user->updatePushSubscription($endpoint, $key, $token, $contentEncoding);

        // Memicu notifikasi push uji coba HANYA 1x saat di-ON-kan secara eksplisit via toggle switch
        if ($request->boolean('send_welcome')) {
            try {
                $user->notify(new \App\Notifications\WebPushGenericNotification(
                    '🎉 Web Push Notification Aktif!',
                    'Selamat! Notifikasi push telah berhasil terhubung dengan perangkat Anda.',
                    url('/admin')
                ));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('[WebPush] Gagal mengirim notifikasi test: '.$e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Push subscription saved successfully.',
            'user_id' => $user->id,
        ], 201);
    }

    /**
     * Kirim notifikasi uji coba manual ke pengguna yang terautentikasi.
     */
    public function sendTestNotification(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        try {
            $user->notify(new \App\Notifications\WebPushGenericNotification(
                '🔔 Uji Coba Web Push Notification',
                'Pesan uji coba ini menandakan notifikasi Web Push berjalan sempurna pada jam '.now()->format('H:i:s').' WITA.',
                url('/admin')
            ));

            return response()->json([
                'message' => 'Notifikasi uji coba telah berhasil dikirim ke perangkat Anda.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Gagal mengirim notifikasi uji coba: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus PushSubscription pengguna.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string',
        ]);

        $user = Auth::user();
        if ($user) {
            $user->deletePushSubscription($request->endpoint);
        }

        return response()->json([
            'message' => 'Push subscription deleted successfully.',
        ]);
    }
}
