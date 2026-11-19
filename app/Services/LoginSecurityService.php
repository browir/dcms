<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLoginHistory;
use App\Notifications\SuspiciousLoginNotification;
use App\Support\BrowserHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoginSecurityService
{
    /**
     * Catat riwayat login dan beri tahu pengguna jika berasal dari IP atau perangkat baru.
     */
    public function recordLogin(User $user, ?Request $request = null): void
    {
        $request = $request ?: request();
        $ip = $request->ip() ?: '127.0.0.1';
        $userAgent = $request->userAgent() ?: '';
        $deviceInfo = BrowserHelper::parse($userAgent);
        $deviceSummary = $deviceInfo['summary'];

        // Cek riwayat login sebelumnya
        $hasHistory = UserLoginHistory::where('user_id', $user->id)->exists();

        if ($hasHistory) {
            $isKnown = UserLoginHistory::where('user_id', $user->id)
                ->where(function ($query) use ($ip, $deviceSummary) {
                    $query->where('ip_address', $ip)
                        ->orWhere('device_summary', $deviceSummary);
                })
                ->exists();

            if (! $isKnown) {
                // Perangkat / IP baru terdeteksi!
                try {
                    $user->notify(new SuspiciousLoginNotification($ip, $userAgent));
                } catch (\Throwable $e) {
                    Log::warning('[Security] Gagal mengirim notifikasi login baru ke email: ' . $e->getMessage());
                }

                // Catat aktivitas di Audit Trail
                activity('keamanan')
                    ->performedOn($user)
                    ->causedBy($user)
                    ->withProperties([
                        'ip'             => $ip,
                        'device_summary' => $deviceSummary,
                    ])
                    ->event('new_device_login')
                    ->log("Masuk dari perangkat atau IP baru ({$deviceSummary})");
            }
        }

        // Catat riwayat login baru
        UserLoginHistory::create([
            'user_id'        => $user->id,
            'ip_address'     => $ip,
            'user_agent'     => $userAgent,
            'device_summary' => $deviceSummary,
            'login_at'       => now(),
        ]);
    }
}

