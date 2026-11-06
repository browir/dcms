<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kode OTP
    |--------------------------------------------------------------------------
    */
    'code_length'       => 6,
    'code_ttl_minutes'  => 10,   // Masa berlaku kode
    'max_attempts'      => 5,    // Batas percobaan salah sebelum kode dibatalkan

    /*
    |--------------------------------------------------------------------------
    | Kirim Ulang
    |--------------------------------------------------------------------------
    */
    'resend_cooldown_seconds' => 60,   // Jeda minimum antar permintaan
    'resend_max_per_hour'     => 5,    // Batas permintaan per jam per pengguna

    /*
    |--------------------------------------------------------------------------
    | Perangkat Tepercaya
    |--------------------------------------------------------------------------
    | Pilihan durasi (hari) yang disajikan kepada pengguna.
    | Default dipilih pada indeks 'default_duration_days'.
    */
    'trusted_device_durations' => [7, 30, 90],  // Pilihan (hari)
    'default_duration_days'    => 30,            // Dipilih saat user belum mengatur

    /*
    |--------------------------------------------------------------------------
    | Peran yang DIWAJIBKAN menggunakan 2FA
    |--------------------------------------------------------------------------
    | Kosongkan array ini jika 2FA sepenuhnya opsional.
    | Contoh: ['super_admin', 'admin']
    */
    'required_roles' => [],

    /*
    |--------------------------------------------------------------------------
    | Cookie Perangkat Tepercaya
    |--------------------------------------------------------------------------
    */
    'cookie_name'     => 'dcms_trusted_device',
    'cookie_secure'   => true,
    'cookie_samesite' => 'Lax',

];
