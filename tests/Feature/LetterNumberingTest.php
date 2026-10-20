<?php

namespace Tests\Feature;

use App\Enum\JenisSurat;
use App\Models\Company;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\LetterNumber;
use App\Models\Unit;
use App\Models\User;
use App\Services\LetterNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Format nomor surat: {urut:3}/{jenis}.{kode_unit}/{kode_surat_perusahaan}/{bulan_romawi}/{tahun}
 * Aturan lengkap: PENOMORAN-SURAT-HRIS.md.
 */
class LetterNumberingTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Department $department;

    private Unit $unit;

    private DocumentCategory $category;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // Event `created` Document mencari penerima notifikasi berdasarkan role ini.
        Role::firstOrCreate(['name' => 'kabid']);
        Role::firstOrCreate(['name' => 'super_admin']);

        $this->company = Company::create(['name' => 'PT Syifa Global Group', 'code' => 'SYIFA', 'kode_surat' => 'SGG']);
        $this->department = Department::create(['name' => 'Umum', 'code' => 'UMM', 'company_id' => $this->company->id]);
        $this->unit = Unit::create(['name' => 'Kepegawaian', 'prefix' => '2', 'department_id' => $this->department->id]);
        $this->category = DocumentCategory::create(['name' => 'Kebijakan', 'prefix' => 'KBJ', 'company_id' => $this->company->id]);
        $this->user = User::create([
            'name' => 'Penulis',
            'username' => 'penulis',
            'email' => 'penulis@example.test',
            'password' => 'password',
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'unit_id' => $this->unit->id,
        ]);
    }

    private function makeDocument(array $attributes = []): Document
    {
        return Document::create([
            'title' => 'Dokumen uji',
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'unit_id' => $this->unit->id,
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
            'jenis_surat' => JenisSurat::KEBIJAKAN_PERATURAN,
            'tanggal_surat' => '2025-07-15',
            'document_type' => 'file',
            ...$attributes,
        ]);
    }

    public function test_nomor_mengikuti_format_hris(): void
    {
        $this->assertSame('001/A.2/SGG/VII/2025', $this->makeDocument()->code_number);
    }

    public function test_counter_dipakai_bersama_semua_jenis_dan_unit_dalam_satu_perusahaan(): void
    {
        $otherUnit = Unit::create(['name' => 'Keuangan', 'prefix' => 'KEU', 'department_id' => $this->department->id]);

        $this->makeDocument();
        $second = $this->makeDocument(['jenis_surat' => JenisSurat::SURAT_UNDANGAN, 'unit_id' => $otherUnit->id, 'tanggal_surat' => '2025-08-01']);

        $this->assertSame('002/H.KEU/SGG/VIII/2025', $second->code_number);
    }

    public function test_counter_terpisah_per_perusahaan(): void
    {
        $other = Company::create(['name' => 'RSU Syifa Medika', 'code' => 'RSUSM']);
        $dept = Department::create(['name' => 'Umum', 'code' => 'UMM', 'company_id' => $other->id]);
        $unit = Unit::create(['name' => 'Umum', 'prefix' => '1', 'department_id' => $dept->id]);

        $this->makeDocument();
        $doc = $this->makeDocument(['company_id' => $other->id, 'department_id' => $dept->id, 'unit_id' => $unit->id]);

        // Perusahaan tanpa kode_surat memakai kode perusahaan.
        $this->assertSame('001/A.1/RSUSM/VII/2025', $doc->code_number);
    }

    public function test_tahun_dan_bulan_diambil_dari_tanggal_surat_bukan_hari_ini(): void
    {
        Carbon::setTestNow('2026-02-10');

        $this->makeDocument(['tanggal_surat' => '2026-01-05']);
        $backdated = $this->makeDocument(['tanggal_surat' => '2025-12-30']);
        $thisYear = $this->makeDocument(['tanggal_surat' => '2026-02-10']);

        $this->assertSame('001/A.2/SGG/XII/2025', $backdated->code_number);
        $this->assertSame('002/A.2/SGG/II/2026', $thisYear->code_number);
    }

    public function test_urut_lewat_999_jadi_empat_digit(): void
    {
        DB::table('letter_number_counters')->insert(['company_id' => $this->company->id, 'year' => 2025, 'last_number' => 999]);

        $this->assertSame('1000/A.2/SGG/VII/2025', $this->makeDocument()->code_number);
    }

    public function test_tanggal_surat_default_hari_ini(): void
    {
        Carbon::setTestNow('2026-10-19');

        $doc = $this->makeDocument(['tanggal_surat' => null]);

        $this->assertSame('001/A.2/SGG/X/2026', $doc->code_number);
        $this->assertSame('2026-10-19', $doc->tanggal_surat->toDateString());
    }

    public function test_dokumen_tanpa_nomor_surat_ditolak(): void
    {
        $this->expectException(ValidationException::class);

        $this->makeDocument(['jenis_surat' => null]);
    }

    public function test_setiap_dokumen_tercatat_di_register_nomor_surat(): void
    {
        $doc = $this->makeDocument();

        $this->assertNotNull($doc->letterNumber);
        $this->assertSame($doc->code_number, $doc->letterNumber->nomor);
        $this->assertTrue($doc->letterNumber->isUsed());
    }

    public function test_dokumen_diisi_ke_nomor_yang_sudah_diambil(): void
    {
        $letter = app(LetterNumberService::class)->issue(
            $this->unit, JenisSurat::SURAT_KEPUTUSAN, Carbon::parse('2025-03-02'), 'SK Tim Mutu', by: $this->user,
        );

        $this->assertSame('001/E.2/SGG/III/2025', $letter->nomor);
        $this->assertSame(1, LetterNumber::query()->available()->count());

        // Organisasi & jenis dokumen mengikuti nomor, bukan isian dokumen.
        $doc = $this->makeDocument(['letter_number_id' => $letter->id, 'jenis_surat' => JenisSurat::SOP, 'tanggal_surat' => '2026-01-01']);

        $this->assertSame('001/E.2/SGG/III/2025', $doc->code_number);
        $this->assertSame(JenisSurat::SURAT_KEPUTUSAN, $doc->jenis_surat);
        $this->assertSame('2025-03-02', $doc->tanggal_surat->toDateString());
        $this->assertSame(0, LetterNumber::query()->available()->count());
        // Nomor yang dipakai tidak memajukan counter lagi.
        $this->assertSame(1, (int) DB::table('letter_number_counters')->value('last_number'));
    }

    public function test_nomor_yang_sudah_dipakai_tidak_bisa_dipakai_lagi(): void
    {
        $doc = $this->makeDocument();

        try {
            $this->makeDocument(['letter_number_id' => $doc->letter_number_id]);
            $this->fail('Nomor surat yang sudah dipakai seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('letter_number_id', $e->errors());
        }

        // Dokumen di Recycle Bin tetap memegang nomornya.
        $doc->delete();
        $this->assertSame(0, LetterNumber::query()->available()->count());
    }

    public function test_insert_gagal_tidak_memakai_nomor(): void
    {
        $this->makeDocument();

        try {
            // user_id wajib — insert gagal setelah nomor diambil di event creating.
            $this->makeDocument(['user_id' => null]);
        } catch (\Throwable) {
        }

        $this->assertSame('002/A.2/SGG/VII/2025', $this->makeDocument()->code_number);
    }

    public function test_nomor_tidak_berubah_saat_dokumen_diedit(): void
    {
        $doc = $this->makeDocument();
        $doc->update(['title' => 'Judul baru', 'tanggal_surat' => '2025-09-01']);

        $this->assertSame('001/A.2/SGG/VII/2025', $doc->fresh()->code_number);
    }

    public function test_pratinjau_sama_dengan_nomor_yang_terbit(): void
    {
        $this->makeDocument();
        $service = app(LetterNumberService::class);

        $preview = $service->preview($this->company, JenisSurat::SURAT_KEPUTUSAN, $this->unit, Carbon::parse('2025-07-20'));

        $this->assertSame($preview, $this->makeDocument(['jenis_surat' => JenisSurat::SURAT_KEPUTUSAN, 'tanggal_surat' => '2025-07-20'])->code_number);
        $this->assertSame('002/E.2/SGG/VII/2025', $preview);
    }
}
