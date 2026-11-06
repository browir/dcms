<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('meetings:complete-overdue')->daily();
Schedule::command('documents:send-expiry-reminders')->dailyAt('07:00');
Schedule::command('meetings:send-reminders')->everyFifteenMinutes();

// Bersihkan kode OTP dan perangkat tepercaya yang sudah kedaluwarsa setiap malam
Schedule::command('two-factor:cleanup')->dailyAt('02:00');
