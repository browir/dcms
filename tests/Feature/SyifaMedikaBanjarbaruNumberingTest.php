<?php

namespace Tests\Feature;

use App\Enum\JenisSurat as EnumJenisSurat;
use App\Enums\JenisSurat as EnumsJenisSurat;
use App\Models\Company;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\Unit;
use App\Models\User;
use App\Services\LetterNumberService;
use App\Services\NomorSuratService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SyifaMedikaBanjarbaruNumberingTest extends TestCase
{
    use RefreshDatabase;

    private Company $rsusm;
    private Department $deptMedik;
    private Unit $lab;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rsusm = Company::create([
            'id' => 5,
            'name' => 'RSU Syifa Medika Banjarbaru',
            'code' => 'RSUSMBJB',
            'kode_surat' => 'RSUSM',
        ]);

        $this->deptMedik = Department::create([
            'company_id' => $this->rsusm->id,
            'name' => 'Penunjang Medik Banjarbaru',
            'code' => 'JANGMEDBJB',
        ]);

        $this->lab = Unit::create([
            'company_id' => $this->rsusm->id,
            'department_id' => $this->deptMedik->id,
            'name' => 'Instalasi Laboratorium',
            'kode' => '16',
            'prefix' => '16',
            'is_active' => true,
        ]);
    }

    public function test_document_categories_a_through_z_for_rsusm(): void
    {
        DocumentCategory::create([
            'company_id' => $this->rsusm->id,
            'name' => 'SPO',
            'prefix' => 'C',
            'code' => 'C',
            'kelompok' => 'dokumen',
            'is_active' => true,
        ]);

        $usable = DocumentCategory::usableFor($this->rsusm->id)->get();
        $this->assertCount(1, $usable);
        $this->assertSame('C', $usable->first()->code);
        $this->assertSame('SPO', $usable->first()->name);

        $forCode = DocumentCategory::forCode('C', $this->rsusm->id);
        $this->assertNotNull($forCode);
        $this->assertSame('SPO', $forCode->name);
        $this->assertSame($this->rsusm->id, $forCode->company_id);
    }

    public function test_letter_number_service_generates_correct_format_for_rsusm(): void
    {
        $category = DocumentCategory::create([
            'company_id' => $this->rsusm->id,
            'name' => 'SPO',
            'prefix' => 'C',
            'code' => 'C',
            'kelompok' => 'dokumen',
            'is_active' => true,
        ]);

        $service = app(LetterNumberService::class);
        $tanggal = Carbon::create(2026, 11, 16);

        $letter = $service->issue(
            $this->lab,
            $category,
            $tanggal,
            'SPO Pengambilan Sampel Darah'
        );

        $this->assertSame('001/C.16/RSUSM/XI/2026', $letter->nomor);
        $this->assertSame(1, $letter->nomor_urut);
        $this->assertSame(EnumJenisSurat::EDARAN_PENGUMUMAN, $letter->jenis_surat); // code C
    }

    public function test_nomor_surat_service_generates_correct_format_for_rsusm(): void
    {
        $formatted = NomorSuratService::formatNomor(
            1,
            EnumsJenisSurat::D->value, // Surat Edaran
            $this->lab->kode,          // 16
            $this->rsusm->kode_surat,  // RSUSM
            11,
            2026
        );

        $this->assertSame('001/D.16/RSUSM/XI/2026', $formatted);
    }

    public function test_all_26_letters_a_through_z_are_valid_in_both_enums(): void
    {
        foreach (range('A', 'Z') as $char) {
            $this->assertNotNull(EnumJenisSurat::tryFrom($char), "App\\Enum\\JenisSurat should have case for {$char}");
            $this->assertNotNull(EnumsJenisSurat::tryFrom($char), "App\\Enums\\JenisSurat should have case for {$char}");
        }
    }
}

