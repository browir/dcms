<?php

namespace Tests\MySQL;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\BeforeClass;

/**
 * Base class untuk semua test MySQL (tests/MySQL/).
 *
 * Pengaman wajib di setUp():
 *   1. Verifikasi koneksi menunjuk ke "dcms_test" — bukan "dcms" atau database lain.
 *   2. Jika database bukan dcms_test, test langsung di-FAIL sebelum menyentuh data.
 *
 * Aturan yang WAJIB diikuti di semua subclass:
 *   - JANGAN gunakan RefreshDatabase atau DatabaseMigrations trait.
 *   - JANGAN truncate tabel production secara tidak sengaja.
 *   - setUp() parent HARUS dipanggil: parent::setUp()
 *   - Setiap test HARUS membersihkan data yang dibuatnya sendiri (tearDown atau transaksi).
 */
abstract class MySQLTestCase extends TestCase
{
    /** Nama database yang SATU-SATUNYA diizinkan untuk test ini. */
    private const ALLOWED_DATABASE = 'dcms_test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertDatabaseIsSafe();
    }

    /**
     * Pastikan koneksi DB menunjuk ke database test yang benar.
     * Fail segera jika tidak — sebelum satu baris data pun disentuh.
     */
    private function assertDatabaseIsSafe(): void
    {
        $currentDb = DB::connection()->getDatabaseName();

        echo PHP_EOL . '[MySQL Guard] Database aktif: ' . $currentDb . PHP_EOL;

        if ($currentDb !== self::ALLOWED_DATABASE) {
            $this->fail(
                PHP_EOL
                . '=====================================================================' . PHP_EOL
                . 'BAHAYA: Test MySQL mendeteksi koneksi ke database yang SALAH!' . PHP_EOL
                . '  Database aktif  : ' . $currentDb . PHP_EOL
                . '  Database wajib  : ' . self::ALLOWED_DATABASE . PHP_EOL
                . '---------------------------------------------------------------------' . PHP_EOL
                . 'Test dihentikan untuk melindungi data.' . PHP_EOL
                . 'Jalankan dengan: php artisan test --configuration=phpunit.mysql.xml' . PHP_EOL
                . '====================================================================='
            );
        }
    }
}
