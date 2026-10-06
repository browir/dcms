<?php

namespace Tests\MySQL;

use App\Enums\JenisSurat;
use App\Models\Unit;
use App\Services\NomorSuratService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit/integration test NomorSuratService terhadap MySQL dcms_test.
 * Mencakup validasi, scope counter, format nomor, dan atomicity.
 *
 * Jalankan dengan: vendor\bin\phpunit -c phpunit.mysql.xml
 */
class NomorSuratServiceTest extends MySQLTestCase
{
    private const PREFIX = 'TSTSVC_';
    private const TAHUN  = 2098;

    /** @var array<int> IDs yang harus dihapus di tearDown */
    private array $companyIds    = [];
    private array $departmentIds = [];
    private array $unitIds       = [];

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    // ── Helper: buat data minimal ─────────────────────────────────────────────

    /**
     * Buat company + department + unit yang valid untuk generate().
     *
     * @return array{company_id: int, dept_id: int, unit_id: int, unit: Unit}
     */
    private function buatUnitValid(
        string $suffix       = 'A',
        string $kodePerush   = 'TSTP',
        string $kodeUnit     = 'TST',
        ?int   $deptCompany  = null,   // jika null, pakai company yang sama
    ): array {
        $companyId = DB::table('companies')->insertGetId([
            'name'       => self::PREFIX . "COMPANY_{$suffix}",
            'code'       => "TC{$suffix}",
            'kode_surat' => $kodePerush,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->companyIds[] = $companyId;

        $deptCompanyId = $deptCompany ?? $companyId;

        $deptId = DB::table('departments')->insertGetId([
            'name'       => self::PREFIX . "DEPT_{$suffix}",
            'code'       => "TD{$suffix}",
            'company_id' => $deptCompanyId,
            'is_active'  => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->departmentIds[] = $deptId;

        $unitId = DB::table('units')->insertGetId([
            'name'          => self::PREFIX . "UNIT_{$suffix}",
            'kode'          => $kodeUnit,
            'department_id' => $deptId,
            'company_id'    => $companyId,
            'is_active'     => 1,
            'created_at'    => now(), 'updated_at' => now(),
        ]);
        $this->unitIds[] = $unitId;

        return [
            'company_id' => $companyId,
            'dept_id'    => $deptId,
            'unit_id'    => $unitId,
            'unit'       => Unit::with(['company', 'department'])->findOrFail($unitId),
        ];
    }

    private function cleanup(): void
    {
        if (! empty($this->unitIds)) {
            DB::table('surat')->whereIn('unit_id', $this->unitIds)->delete();
            DB::table('nomor_surat_counter')
                ->whereIn('perusahaan_id', $this->companyIds ?: [0])
                ->delete();
        }
        DB::table('units')->where('name', 'LIKE', self::PREFIX . '%')->delete();
        DB::table('departments')->where('name', 'LIKE', self::PREFIX . '%')->delete();
        DB::table('companies')->where('name', 'LIKE', self::PREFIX . '%')->delete();
    }

    private function dataOk(): array
    {
        return ['judul_surat' => 'Test', 'dibuat_oleh_nama' => 'Tester'];
    }

    private function counterValue(int $companyId, int $tahun): ?int
    {
        $row = DB::table('nomor_surat_counter')
            ->where('perusahaan_id', $companyId)
            ->where('tahun', $tahun)
            ->first();
        return $row ? (int) $row->nomor_terakhir : null;
    }

    // ── Item 6a: Validasi → RuntimeException, counter tidak tersentuh ─────────

    #[Test]
    public function unit_tanpa_kode_melempar_exception_dan_counter_tidak_berubah(): void
    {
        $d = $this->buatUnitValid('NOKODE');

        // Update kode menjadi NULL
        DB::table('units')->where('id', $d['unit_id'])->update(['kode' => null]);
        $unit = Unit::with(['company', 'department'])->findOrFail($d['unit_id']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/belum memiliki kode unit/');

        try {
            NomorSuratService::generate(
                $unit, JenisSurat::G, Carbon::create(self::TAHUN, 6, 1), $this->dataOk()
            );
        } finally {
            // Counter tidak boleh ada (tidak pernah disentuh)
            $this->assertNull(
                $this->counterValue($d['company_id'], self::TAHUN),
                'Counter tidak boleh disentuh saat validasi gagal'
            );
        }
    }

    #[Test]
    public function unit_tanpa_company_id_melempar_exception_dan_counter_tidak_berubah(): void
    {
        $d = $this->buatUnitValid('NOCOID');
        DB::table('units')->where('id', $d['unit_id'])->update(['company_id' => null]);
        $unit = Unit::with(['company', 'department'])->findOrFail($d['unit_id']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/belum memiliki company_id/');

        try {
            NomorSuratService::generate(
                $unit, JenisSurat::G, Carbon::create(self::TAHUN, 6, 1), $this->dataOk()
            );
        } finally {
            $this->assertNull(
                $this->counterValue($d['company_id'], self::TAHUN),
                'Counter tidak boleh disentuh saat validasi gagal'
            );
        }
    }

    #[Test]
    public function perusahaan_tanpa_kode_surat_melempar_exception_dan_counter_tidak_berubah(): void
    {
        $d = $this->buatUnitValid('NOKDSRT');
        DB::table('companies')->where('id', $d['company_id'])->update(['kode_surat' => null]);
        $unit = Unit::with(['company', 'department'])->findOrFail($d['unit_id']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/belum memiliki kode_surat/');

        try {
            NomorSuratService::generate(
                $unit, JenisSurat::G, Carbon::create(self::TAHUN, 6, 1), $this->dataOk()
            );
        } finally {
            $this->assertNull(
                $this->counterValue($d['company_id'], self::TAHUN),
                'Counter tidak boleh disentuh saat validasi gagal'
            );
        }
    }

    #[Test]
    public function inkonsistensi_company_unit_dan_department_melempar_exception(): void
    {
        // Buat company kedua untuk department
        $company2Id = DB::table('companies')->insertGetId([
            'name'       => self::PREFIX . 'COMPANY_B2',
            'code'       => 'TCB2',
            'kode_surat' => 'TSTB',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->companyIds[] = $company2Id;

        // Unit company_id = A, Department company_id = B (berbeda → inkonsisten)
        $d = $this->buatUnitValid('INKONS', 'TSTK', 'INK', $company2Id);

        $unit = Unit::with(['company', 'department'])->findOrFail($d['unit_id']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Inkonsistensi data/');

        NomorSuratService::generate(
            $unit, JenisSurat::G, Carbon::create(self::TAHUN, 6, 1), $this->dataOk()
        );
    }

    // ── Item 6b: Tanggal surat 2025, dibuat di 2026 → counter tahun 2025 ──────

    #[Test]
    public function tanggal_surat_2025_memakai_counter_tahun_2025_dan_nomor_berakhiran_2025(): void
    {
        $tahunSurat  = self::TAHUN - 1; // 2097
        $tahunBerjalan = self::TAHUN;   // 2098 (bukan 2097)

        $d    = $this->buatUnitValid('TGLTHN');
        $unit = $d['unit'];
        $tgl  = Carbon::create($tahunSurat, 9, 15); // tanggal di tahun lalu

        $surat = NomorSuratService::generate($unit, JenisSurat::G, $tgl, $this->dataOk());

        // Counter harus di tahun surat (2097), bukan tahun berjalan (2098)
        $this->assertNotNull(
            $this->counterValue($d['company_id'], $tahunSurat),
            "Counter tahun {$tahunSurat} harus terbuat"
        );
        $this->assertNull(
            $this->counterValue($d['company_id'], $tahunBerjalan),
            "Counter tahun {$tahunBerjalan} tidak boleh tersentuh"
        );

        // Nomor harus berakhiran /{tahunSurat}
        $this->assertStringEndsWith(
            "/{$tahunSurat}",
            $surat->nomor,
            "Nomor surat harus berakhiran /{$tahunSurat}, dapat: {$surat->nomor}"
        );

        // Verifikasi nomor urut dimulai dari 001 untuk tahun itu
        $this->assertEquals(1, $surat->nomor_urut);
        $this->assertStringStartsWith('001/', $surat->nomor);
    }

    // ── Item 6c: Counter terpisah antar perusahaan dan antar tahun ───────────

    #[Test]
    public function counter_terpisah_per_perusahaan(): void
    {
        $dA = $this->buatUnitValid('CPA', 'TSTA', 'CPA');
        $dB = $this->buatUnitValid('CPB', 'TSTB', 'CPB');

        $tgl = Carbon::create(self::TAHUN, 3, 1);

        // Buat 3 surat di perusahaan A
        for ($i = 0; $i < 3; $i++) {
            NomorSuratService::generate($dA['unit'], JenisSurat::G, $tgl, $this->dataOk());
        }

        // Buat 2 surat di perusahaan B
        for ($i = 0; $i < 2; $i++) {
            NomorSuratService::generate($dB['unit'], JenisSurat::G, $tgl, $this->dataOk());
        }

        // Counter A = 3, Counter B = 2 (terpisah)
        $this->assertEquals(3, $this->counterValue($dA['company_id'], self::TAHUN),
            'Counter perusahaan A harus 3');
        $this->assertEquals(2, $this->counterValue($dB['company_id'], self::TAHUN),
            'Counter perusahaan B harus 2 (tidak terpengaruh A)');
    }

    #[Test]
    public function counter_terpisah_per_tahun(): void
    {
        $d    = $this->buatUnitValid('CTH');
        $unit = $d['unit'];

        $tgl2097 = Carbon::create(self::TAHUN - 1, 12, 31);
        $tgl2098 = Carbon::create(self::TAHUN, 1, 1);

        // 2 surat tahun 2097, 3 surat tahun 2098
        for ($i = 0; $i < 2; $i++) {
            NomorSuratService::generate($unit, JenisSurat::G, $tgl2097, $this->dataOk());
        }
        for ($i = 0; $i < 3; $i++) {
            NomorSuratService::generate($unit, JenisSurat::G, $tgl2098, $this->dataOk());
        }

        $this->assertEquals(2, $this->counterValue($d['company_id'], self::TAHUN - 1),
            'Counter tahun 2097 harus 2');
        $this->assertEquals(3, $this->counterValue($d['company_id'], self::TAHUN),
            'Counter tahun 2098 harus 3 (terpisah dari 2097)');

        // Surat pertama tahun baru harus mulai dari 001
        $suratPertama2098 = DB::table('surat')
            ->where('unit_id', $d['unit_id'])
            ->where('tanggal', $tgl2098->format('Y-m-d'))
            ->orderBy('nomor_urut')
            ->first();

        $this->assertNotNull($suratPertama2098);
        $this->assertEquals(1, $suratPertama2098->nomor_urut,
            'Surat pertama tahun baru harus nomor_urut=1');
        $this->assertStringStartsWith('001/', $suratPertama2098->nomor);
    }

    // ── Item 6d: Counter di-set 999 → nomor berikutnya "1000/..." ────────────

    #[Test]
    public function setelah_counter_999_nomor_berikutnya_adalah_1000(): void
    {
        $d    = $this->buatUnitValid('C999');
        $unit = $d['unit'];
        $tgl  = Carbon::create(self::TAHUN, 1, 1);

        // Set counter langsung ke 999 (simulasi sudah ada 999 surat)
        DB::table('nomor_surat_counter')->insert([
            'perusahaan_id'  => $d['company_id'],
            'tahun'          => self::TAHUN,
            'nomor_terakhir' => 999,
        ]);

        $surat = NomorSuratService::generate($unit, JenisSurat::G, $tgl, $this->dataOk());

        $this->assertEquals(1000, $surat->nomor_urut,
            'Nomor urut ke-1000 harus = 1000');

        // Format: 1000 tanpa padding (bukan 1000 dengan padding)
        $this->assertStringStartsWith('1000/', $surat->nomor,
            "Nomor harus dimulai '1000/', dapat: {$surat->nomor}");

        $this->assertEquals(1000, $this->counterValue($d['company_id'], self::TAHUN));
    }
}
