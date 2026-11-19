<?php

namespace App\Rules;

use App\Models\PasswordHistory;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

class NotPreviousPassword implements ValidationRule
{
    public function __construct(
        protected ?User $user = null,
        protected int $historyCount = 3,
    ) {
        $this->user = $user ?: auth()->user();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->user) {
            return;
        }

        // Cek apakah sama dengan kata sandi aktif saat ini
        if ($this->user->password && Hash::check($value, $this->user->password)) {
            $fail('Kata sandi baru tidak boleh sama dengan kata sandi yang sedang digunakan saat ini.');
            return;
        }

        // Cek riwayat kata sandi sebelumnya
        $histories = PasswordHistory::where('user_id', $this->user->id)
            ->latest()
            ->take($this->historyCount)
            ->get();

        foreach ($histories as $history) {
            if (Hash::check($value, $history->password)) {
                $fail("Demi keamanan, kata sandi baru tidak boleh sama dengan salah satu dari {$this->historyCount} kata sandi terakhir Anda.");
                return;
            }
        }
    }
}

