<?php

namespace App\Filament\Components;

use App\Support\BrowserHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ActiveSessions extends Component
{
    public bool $confirmingLogout = false;
    public string $password = '';
    public string $errorMsg = '';
    public string $successMsg = '';

    #[Computed]
    public function sessions(): array
    {
        $userId = Auth::id();
        $currentSessionId = request()->hasSession() ? request()->session()->getId() : '';

        $records = DB::table('sessions')
            ->where('user_id', $userId)
            ->orderBy('last_activity', 'desc')
            ->get();

        $items = [];
        $hasCurrent = false;

        foreach ($records as $record) {
            $isCurrent = ($record->id === $currentSessionId);
            if ($isCurrent) {
                $hasCurrent = true;
            }

            $items[] = [
                'id'                => $record->id,
                'ip_address'        => $record->ip_address ?? '—',
                'user_agent'        => $record->user_agent ?? '',
                'last_active'       => Carbon::createFromTimestamp($record->last_activity)->diffForHumans(),
                'is_current_device' => $isCurrent,
                'browser_info'      => BrowserHelper::parse($record->user_agent),
            ];
        }

        // Jika sesi saat ini belum tersimpan di DB (misal saat transisi awal), sintesis sesi saat ini
        if (! $hasCurrent) {
            array_unshift($items, [
                'id'                => $currentSessionId,
                'ip_address'        => request()->ip() ?? '127.0.0.1',
                'user_agent'        => request()->userAgent() ?? '',
                'last_active'       => 'Aktif sekarang',
                'is_current_device' => true,
                'browser_info'      => BrowserHelper::parse(request()->userAgent()),
            ]);
        }

        return $items;
    }

    public function confirmLogoutOtherDevices(): void
    {
        $this->resetErrorBag();
        $this->errorMsg = '';
        $this->password = '';
        $this->confirmingLogout = true;
    }

    public function cancelLogout(): void
    {
        $this->confirmingLogout = false;
        $this->password = '';
        $this->errorMsg = '';
    }

    public function logoutOtherDevices(): void
    {
        $this->resetErrorBag();
        $this->errorMsg = '';

        if (empty($this->password)) {
            $this->errorMsg = 'Silakan masukkan kata sandi Anda untuk konfirmasi.';
            return;
        }

        $user = Auth::user();

        if (! Hash::check($this->password, $user->password)) {
            $this->errorMsg = 'Kata sandi yang Anda masukkan tidak sesuai.';
            return;
        }

        $currentSessionId = request()->hasSession() ? request()->session()->getId() : '';

        // Hapus sesi lain dari tabel database
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        // Panggil helper bawaan Laravel untuk menginvaliasi remember token / guard session lain
        try {
            Auth::logoutOtherDevices($this->password);
        } catch (\Throwable $e) {
            // Ignore jika password re-hash berbeda
        }

        // Catat ke audit log
        activity('keamanan')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'ip'         => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->event('sessions_revoked')
            ->log('Mengeluarkan semua sesi di perangkat lain');

        $this->confirmingLogout = false;
        $this->password = '';
        $this->successMsg = 'Semua sesi di perangkat lain berhasil dikeluarkan.';
    }

    public function logoutSingleSession(string $sessionId): void
    {
        $currentSessionId = request()->hasSession() ? request()->session()->getId() : '';

        if ($sessionId && $sessionId === $currentSessionId) {
            return;
        }

        $user = Auth::user();

        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();

        activity('keamanan')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'session_id' => substr($sessionId, 0, 8) . '...',
                'ip'         => request()->ip(),
            ])
            ->event('session_terminated')
            ->log('Mengeluarkan satu sesi perangkat');

        $this->successMsg = 'Perangkat berhasil dikeluarkan.';
    }

    public function dismissSuccess(): void
    {
        $this->successMsg = '';
    }

    public function dismissError(): void
    {
        $this->errorMsg = '';
    }

    public function render()
    {
        return view('filament.components.active-sessions');
    }
}
