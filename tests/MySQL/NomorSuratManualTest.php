<?php

namespace Tests\MySQL;

use App\Enums\JenisSurat;
use App\Models\Surat;
use App\Models\Unit;
use App\Models\User;
use App\Services\NomorSuratManualService;
use App\Services\NomorSuratService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

/**
 * Integration test untuk fitur penomoran surat manual.
 * Menguji pembuatan surat manual, validasi keunikan, counter isolation,
 * collision skip pada generator otomatis, audit log, dan pergantian mode.
 */
class NomorSuratManualTest extends MySQLTestCase
{
    private const PREFIX = 'TSTMNL_';
    private const TAHUN  = 2099;

    /** @var array<int> IDs yang harus dihapus di tearDown */
    private array $companyIds    = [];
    private array $departmentIds = [];
    private array $unitIds       = [];
    private array $suratIds      = [];
    private array $userIds       = [];

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    private function cleanup(): void
    {
        if (! empty($this->suratIds)) {
            DB::table('activity_log')->where('subject_type', Surat::class)->whereIn('subject_id', $this->suratIds)->delete();
            DB::table('surat')->whereIn('id', $this->suratIds)->delete();
            $this->suratIds = [];
        }

        DB::table('nomor_surat_counter')->where('tahun', self::TAHUN)->delete();

        if (! empty($this->unitIds)) {
            DB::table('units')->whereIn('id', $this->unitIds)->delete();
            $this->unitIds = [];
        }
        if (! empty($this->departmentIds)) {
            DB::table('departments')->whereIn('id', $this->departmentIds)->delete();
            $this->departmentIds = [];
        }
        if (! empty($this->companyIds)) {
            DB::table('companies')->whereIn('id', $this->companyIds)->delete();
            $this->companyIds = [];
        }
        if (! empty($this->userIds)) {
            DB::table('model_has_roles')->whereIn('model_id', $this->userIds)->delete();
            DB::table('users')->whereIn('id', $this->userIds)->delete();
            $this->userIds = [];
        }
    }

    private function buatUnitValid(string $suffix = 'A', string $kodePerush = 'TSTP', string $kodeUnit = 'TST'): array
    {
        $companyId = DB::table('companies')->insertGetId([
            'name'       => self::PREFIX . "COMPANY_{$suffix}",
            'code'       => "TC{$suffix}",
            'kode_surat' => $kodePerush,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->companyIds[] = $companyId;

        $deptId = DB::table('departments')->insertGetId([
            'name'       => self::PREFIX . "DEPT_{$suffix}",
            'code'       => "TD{$suffix}",
            'company_id' => $companyId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->departmentIds[] = $deptId;

        $unitId = DB::table('units')->insertGetId([
            'name'          => self::PREFIX . "UNIT_{$suffix}",
            'kode'          => $kodeUnit,
            'company_id'    => $companyId,
            'department_id' => $deptId,
            'is_active'     => 1,
            'created_at'    => now(), 'updated_at' => now(),
        ]);
        $this->unitIds[] = $unitId;

        $unit = Unit::with('company')->findOrFail($unitId);

        return [
            'company_id' => $companyId,
            'dept_id'    => $deptId,
            'unit_id'    => $unitId,
            'unit'       => $unit,
        ];
    }

    private function buatUserDenganRole(string $roleName): User
    {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        $user = User::factory()->create([
            'name'  => self::PREFIX . "USER_{$roleName}",
            'email' => strtolower(self::PREFIX . "user_{$roleName}_" . uniqid() . '@example.test'),
        ]);
        $user->assignRole($roleName);

        $this->userIds[] = $user->id;

        return $user;
    }

    // ── 1. Pembuatan Surat Manual ─────────────────────────────────────────────

    #[Test]
    public function pembuatan_surat_manual_berhasil_dan_nomor_sesuai_input(): void
    {
        $d = $this->buatUnitValid();
        $user = $this->buatUserDenganRole('super_admin');

        $surat = NomorSuratManualService::create(
            unit: $d['unit'],
            jenis: JenisSurat::G,
            tanggal: Carbon::create(self::TAHUN, 3, 15),
            nomorManual: '  007/G.TST/TSTP/III/2099  ',
            data: [
                'judul_surat' => 'Surat Manual Uji',
                'perihal'     => 'Uji coba nomor manual',
            ],
            user: $user,
        );

        $this->suratIds[] = $surat->id;

        $this->assertSame('007/G.TST/TSTP/III/2099', $surat->nomor);
        $this->assertSame(Surat::NUMBERING_MANUAL, $surat->numbering_type);
        $this->assertTrue($surat->isManual());
        $this->assertFalse($surat->isAuto());
        $this->assertNull($surat->nomor_urut);
        $this->assertDatabaseHas('surat', [
            'id'             => $surat->id,
            'nomor'          => '007/G.TST/TSTP/III/2099',
            'numbering_type' => Surat::NUMBERING_MANUAL,
        ]);
    }

    #[Test]
    public function pembuatan_surat_manual_dengan_format_non_standar_tetap_tersimpan(): void
    {
        $d = $this->buatUnitValid();
        $user = $this->buatUserDenganRole('Sekretaris');

        $surat = NomorSuratManualService::create(
            unit: $d['unit'],
            jenis: JenisSurat::G,
            tanggal: Carbon::create(self::TAHUN, 1, 10),
            nomorManual: 'KUS-099/DIR/2099',
            data: ['judul_surat' => 'Surat Khusus Non Standar'],
            user: $user,
        );

        $this->suratIds[] = $surat->id;

        $this->assertSame('KUS-099/DIR/2099', $surat->nomor);
        $this->assertSame(Surat::NUMBERING_MANUAL, $surat->numbering_type);
    }

    // ── 2. Validasi Keunikan ──────────────────────────────────────────────────

    #[Test]
    public function nomor_manual_kosong_ditolak_validasi(): void
    {
        $d = $this->buatUnitValid();
        $user = $this->buatUserDenganRole('super_admin');

        $this->expectException(ValidationException::class);

        NomorSuratManualService::create(
            unit: $d['unit'],
            jenis: JenisSurat::G,
            tanggal: Carbon::create(self::TAHUN, 1, 1),
            nomorManual: "   \t  \n  ",
            data: ['judul_surat' => 'Surat Kosong'],
            user: $user,
        );
    }

    #[Test]
    public function nomor_manual_duplikat_ditolak_secara_case_insensitive_dan_spasi_berlebih(): void
    {
        $d = $this->buatUnitValid();
        $user = $this->buatUserDenganRole('super_admin');

        $surat1 = NomorSuratManualService::create(
            unit: $d['unit'],
            jenis: JenisSurat::G,
            tanggal: Carbon::create(self::TAHUN, 1, 1),
            nomorManual: '001/G.TST/TSTP/I/2099',
            data: ['judul_surat' => 'Surat Pertama'],
            user: $user,
        );
        $this->suratIds[] = $surat1->id;

        $this->expectException(ValidationException::class);

        // Input dengan spasi berlebih dan beda huruf besar/kecil
        NomorSuratManualService::create(
            unit: $d['unit'],
            jenis: JenisSurat::G,
            tanggal: Carbon::create(self::TAHUN, 1, 1),
            nomorManual: '   001/g.tst/tstp/i/2099   ',
            data: ['judul_surat' => 'Surat Duplikat'],
            user: $user,
        );
    }

    #[Test]
    public function nomor_manual_duplikat_dengan_surat_otomatis_ditolak(): void
    {
        $d = $this->buatUnitValid();
        $user = $this->buatUserDenganRole('super_admin');

        // Buat nomor otomatis pertama
        $suratAuto = NomorSuratService::generate(
            unit: $d['unit'],
            jenis: JenisSurat::G,
            tanggal: Carbon::create(self::TAHUN, 5, 1),
            data: ['judul_surat' => 'Surat Otomatis'],
        );
        $this->suratIds[] = $suratAuto->id;

        $this->expectException(ValidationException::class);

        // Coba buat manual dengan nomor yang sama
        NomorSuratManualService::create(
            unit: $d['unit'],
            jenis: JenisSurat::G,
            tanggal: Carbon::create(self::TAHUN, 5, 1),
            nomorManual: strtolower($suratAuto->nomor),
            data: ['judul_surat' => 'Coba Rebut Nomor Auto'],
            user: $user,
        );
    }

    #[Test]
    public function nomor_manual_duplikat_dengan_surat_soft_deleted_ditolak(): void
    {
        $d = $this->buatUnitValid();
        $user = $this->buatUserDenganRole('super_admin');

        $surat1 = NomorSuratManualService::create(
            unit: $d['unit'],
            jenis: JenisSurat::G,
            tanggal: Carbon::create(self::TAHUN, 2, 1),
            nomorManual: 'DEL-001/TST/2099',
            data: ['judul_surat' => 'Surat Dihapus'],
            user: $user,
        );
        $this->suratIds[] = $surat1->id;

        // Soft delete surat1
        $surat1->delete();
        $this->assertSoftDeleted('surat', ['id' => $surat1->id]);

        $this->expectException(ValidationException::class);

        // Nomor yang sama tetap ditolak
        NomorSuratManualService::create(
            unit: $d['unit'],
            jenis: JenisSurat::G,
            tanggal: Carbon::create(self::TAHUN, 2, 2),
            nomorManual: 'del-001/tst/2099',
            data: ['judul_surat' => 'Mencoba Pakai Nomor Terhapus'],
            user: $user,
        );
    }

    // ── 3. Isolasi Counter Otomatis ──────────────────────────────────────────

    #[Test]
    public function pembuatan_surat_manual_tidak_memengaruhi_counter_otomatis(): void
    {
        $d = $this->buatUnitValid();
        $user = $this->buatUserDenganRole('super_admin');

        // Pastikan counter belum ada
        $counterAwal = DB::table('nomor_surat_counter')
            ->where('perusahaan_id', $d['company_id'])
            ->where('tahun', self::TAHUN)
            ->first();
        $this->assertNull($counterAwal);

        // Buat beberapa surat manual
        $s1 = NomorSuratManualService::create($d['unit'], JenisSurat::G, Carbon::create(self::TAHUN, 1, 1), 'MANUAL-01', ['judul_surat' => 'M1'], $user);
        $s2 = NomorSuratManualService::create($d['unit'], JenisSurat::G, Carbon::create(self::TAHUN, 1, 2), 'MANUAL-02', ['judul_surat' => 'M2'], $user);
        $this->suratIds[] = $s1->id;
        $this->suratIds[] = $s2->id;

        // Counter masih belum ada di tabel counter
        $counterTengah = DB::table('nomor_surat_counter')
            ->where('perusahaan_id', $d['company_id'])
            ->where('tahun', self::TAHUN)
            ->first();
        $this->assertNull($counterTengah);

        // Sekarang buat surat otomatis pertama
        $sAuto = NomorSuratService::generate($d['unit'], JenisSurat::G, Carbon::create(self::TAHUN, 1, 3), ['judul_surat' => 'Auto 1']);
        $this->suratIds[] = $sAuto->id;

        // Counter otomatis harus dimulai dari 1!
        $this->assertSame(1, $sAuto->nomor_urut);
        $this->assertSame('001/G.TST/TSTP/I/2099', $sAuto->nomor);

        $counterAkhir = DB::table('nomor_surat_counter')
            ->where('perusahaan_id', $d['company_id'])
            ->where('tahun', self::TAHUN)
            ->value('nomor_terakhir');
        $this->assertSame(1, (int) $counterAkhir);
    }

    // ── 4. Penanganan Tabrakan (Collision Handling) ──────────────────────────

    #[Test]
    public function generator_otomatis_melompati_nomor_jika_sudah_dipakai_oleh_surat_manual(): void
    {
        $d = $this->buatUnitValid();
        $user = $this->buatUserDenganRole('super_admin');

        // Nomor yang seharusnya dihasilkan otomatis untuk urut 1 adalah: 001/G.TST/TSTP/I/2099
        // Pengguna sengaja memasukkan nomor tersebut secara MANUAL terlebih dahulu!
        $sManual = NomorSuratManualService::create(
            unit: $d['unit'],
            jenis: JenisSurat::G,
            tanggal: Carbon::create(self::TAHUN, 1, 1),
            nomorManual: '001/G.TST/TSTP/I/2099',
            data: ['judul_surat' => 'Nomor Manual yang Menyerupai Auto 1'],
            user: $user,
        );
        $this->suratIds[] = $sManual->id;

        // Sekarang panggil generator otomatis
        $sAuto = NomorSuratService::generate(
            unit: $d['unit'],
            jenis: JenisSurat::G,
            tanggal: Carbon::create(self::TAHUN, 1, 1),
            data: ['judul_surat' => 'Surat Auto Pertama yang Menemukan Tabrakan'],
        );
        $this->suratIds[] = $sAuto->id;

        // Generator otomatis harus mendeteksi tabrakan, melompat ke urut 2, dan sukses!
        $this->assertSame(2, $sAuto->nomor_urut);
        $this->assertSame('002/G.TST/TSTP/I/2099', $sAuto->nomor);
    }

    // ── 5. Pengeditan & Audit Log ─────────────────────────────────────────────

    #[Test]
    public function edit_surat_manual_berhasil_dan_mencatat_activity_log(): void
    {
        $d = $this->buatUnitValid();
        $user = $this->buatUserDenganRole('super_admin');

        $surat = NomorSuratManualService::create(
            unit: $d['unit'],
            jenis: JenisSurat::G,
            tanggal: Carbon::create(self::TAHUN, 6, 1),
            nomorManual: 'REV-01/TST/2099',
            data: ['judul_surat' => 'Judul Awal'],
            user: $user,
        );
        $this->suratIds[] = $surat->id;

        // Edit nomor dan judul
        $updated = NomorSuratManualService::update(
            surat: $surat,
            data: [
                'nomor'       => 'REV-02/TST/2099',
                'judul_surat' => 'Judul Baru',
            ],
            user: $user,
        );

        $this->assertSame('REV-02/TST/2099', $updated->nomor);
        $this->assertSame('Judul Baru', $updated->judul_surat);

        // Verifikasi Activity Log
        $log = Activity::forSubject($surat)
            ->where('log_name', 'surat')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->causer_id);
        $this->assertSame('REV-01/TST/2099', $log->properties['nomor_lama']);
        $this->assertSame('REV-02/TST/2099', $log->properties['nomor_baru']);
    }

    #[Test]
    public function edit_surat_manual_dapat_mengabaikan_nomor_sendiri_uniqueness(): void
    {
        $d = $this->buatUnitValid();
        $user = $this->buatUserDenganRole('super_admin');

        $surat = NomorSuratManualService::create(
            unit: $d['unit'],
            jenis: JenisSurat::G,
            tanggal: Carbon::create(self::TAHUN, 6, 1),
            nomorManual: 'SAME-01/TST/2099',
            data: ['judul_surat' => 'Surat Sama'],
            user: $user,
        );
        $this->suratIds[] = $surat->id;

        // Edit tanpa mengubah nomor (misal hanya ubah judul)
        $updated = NomorSuratManualService::update(
            surat: $surat,
            data: [
                'nomor'       => 'same-01/tst/2099', // huruf kecil nomor yang sama
                'judul_surat' => 'Judul Diedit Saja',
            ],
            user: $user,
        );

        $this->assertSame('same-01/tst/2099', $updated->nomor);
        $this->assertSame('Judul Diedit Saja', $updated->judul_surat);
    }

    // ── 6. Pergantian Mode Penomoran ──────────────────────────────────────────

    #[Test]
    public function pergantian_mode_dari_manual_ke_otomatis_menghasilkan_nomor_auto_baru(): void
    {
        $d = $this->buatUnitValid();
        $user = $this->buatUserDenganRole('super_admin');

        $surat = NomorSuratManualService::create(
            unit: $d['unit'],
            jenis: JenisSurat::G,
            tanggal: Carbon::create(self::TAHUN, 7, 10),
            nomorManual: 'MAN-SWAP/TST/2099',
            data: ['judul_surat' => 'Awalnya Manual'],
            user: $user,
        );
        $this->suratIds[] = $surat->id;

        // Ganti ke otomatis
        $updated = NomorSuratManualService::update(
            surat: $surat,
            data: [
                'numbering_type' => Surat::NUMBERING_AUTO,
            ],
            user: $user,
        );

        $this->assertSame(Surat::NUMBERING_AUTO, $updated->numbering_type);
        $this->assertTrue($updated->isAuto());
        $this->assertSame(1, $updated->nomor_urut);
        $this->assertSame('001/G.TST/TSTP/VII/2099', $updated->nomor);
    }

    #[Test]
    public function pergantian_mode_dari_otomatis_ke_manual_membuat_nomor_auto_lama_hangus(): void
    {
        $d = $this->buatUnitValid();
        $user = $this->buatUserDenganRole('super_admin');

        $suratAuto = NomorSuratService::generate(
            unit: $d['unit'],
            jenis: JenisSurat::G,
            tanggal: Carbon::create(self::TAHUN, 8, 1),
            data: ['judul_surat' => 'Surat Auto Asli'],
        );
        $this->suratIds[] = $suratAuto->id;
        $nomorAutoLama = $suratAuto->nomor;

        // Ganti ke manual
        $updated = NomorSuratManualService::update(
            surat: $suratAuto,
            data: [
                'numbering_type' => Surat::NUMBERING_MANUAL,
                'nomor'          => 'MANUAL-REPLACE/2099',
            ],
            user: $user,
        );

        $this->assertSame(Surat::NUMBERING_MANUAL, $updated->numbering_type);
        $this->assertSame('MANUAL-REPLACE/2099', $updated->nomor);
        $this->assertNull($updated->nomor_urut);

        // Surat otomatis berikutnya TIDAK boleh memakai ulang counter urut 1 (counter tidak di-decrement)
        $suratAutoBerikutnya = NomorSuratService::generate(
            unit: $d['unit'],
            jenis: JenisSurat::G,
            tanggal: Carbon::create(self::TAHUN, 8, 2),
            data: ['judul_surat' => 'Surat Auto Kedua'],
        );
        $this->suratIds[] = $suratAutoBerikutnya->id;

        $this->assertSame(2, $suratAutoBerikutnya->nomor_urut);
        $this->assertNotSame($nomorAutoLama, $suratAutoBerikutnya->nomor);
    }

    // ── 7. Otorisasi Hak Akses ────────────────────────────────────────────────

    #[Test]
    public function user_tanpa_hak_akses_ditolak_membuat_surat_manual(): void
    {
        $d = $this->buatUnitValid();
        $userBiasa = $this->buatUserDenganRole('user');

        $this->expectException(AuthorizationException::class);

        NomorSuratManualService::create(
            unit: $d['unit'],
            jenis: JenisSurat::G,
            tanggal: Carbon::create(self::TAHUN, 1, 1),
            nomorManual: '001/G.TST/TSTP/I/2099',
            data: ['judul_surat' => 'Surat Ilegal'],
            user: $userBiasa,
        );
    }
}
